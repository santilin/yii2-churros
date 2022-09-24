<?php
namespace santilin\churros;

use yii;
use yii\helpers\{Html,ArrayHelper};
use yii\db\Query;
use yii\data\ActiveDataProvider;
use santilin\churros\ModelSearchTrait;
use santilin\churros\grid\ReportView;

trait ReportsModelTrait
{
	public $report_columns = [];
	public $report_filters = [];
	public $report_sorting = [];
	public $only_totals = false;
	public $landscape = true;

	public function getReportValue($var, $default)
	{
		$values = unserialize($this->value);
		if( isset($values->$var) ) {
			return $values->$var;
		} else {
			return $default;
		}
	}

	/**
	 * Loads the data from the form POST into the model
	 */
	public function load($data, $formName = null)
	{
		$scope = $formName === null ? $this->formName() : $formName;
		if( isset($data[$scope]['only_totals'] ) ) {
			$this->only_totals = boolval($data[$scope]['only_totals']);
		}
		if( isset($data[$scope]['landscape'] ) ) {
			$this->landscape = boolval($data[$scope]['landscape']);
		}
		if( isset($data['report_columns']) ) {
			// The HTML form sends the data trasposed
			$this->report_columns = [];
			foreach( $data['report_columns']['name'] as $key => $value) {
				if( empty($value) ) {
					continue; // The column has not been specified
				}
				$this->report_columns[] = [
					'attribute' => $value,
					'summary' => $data['report_columns']['summary'][$key],
					'label' => $data['report_columns']['label'][$key],
					'format' => $data['report_columns']['format'][$key]??'raw'
				];
			}
		}
		if( isset($data['report_sorting']) ) {
			$this->report_sorting = [];
			// The HTML form sends the data trasposed
			foreach( $data['report_sorting']['name'] as $key => $value) {
				if( empty($value) ) {
					continue; // The column has not been specified
				}
				$this->report_sorting[] = [
					'attribute' => $value,
					'asc' => $data['report_sorting']['asc'][$key]??SORT_ASC,
					'group' => $data['report_sorting']['group'][$key]??true,
					'show_column' => $data['report_sorting']['show_column'][$key]??false,
					'show_header' => $data['report_sorting']['show_header'][$key]??true,
					'show_footer' => $data['report_sorting']['show_footer'][$key]??true,
				];
			}
		}
		$searchScope = $this->model;
		if( substr($searchScope, -6) != "Search" ) {
			$searchScope .= "Search";
		}
		if( isset($data[$searchScope]) ) {
			$this->report_filters = [];
			$svalues = $data[$searchScope];
			// The HTML form sends the data trasposed
			foreach( $svalues['attribute'] as $key => $value) {
				if( empty($value) ) {
					continue; // The column has not been specified
				}
				if( $svalues['lft']!=='' || $svalues['rgt'] != '' || $svalues['op'] != 'LIKE' ) {
					$this->report_filters[] = [
						'attribute' => $value,
						'lft' => $svalues['lft'][$key],
						'rgt' => $svalues['rgt'][$key],
						'op' => $svalues['op'][$key]??'LIKE'
					];
				}
			}
		}
		return true;
	}

	public function encodeValue()
	{
		$value = json_decode($this->value, false);
		if( $value === null ) {
			$value = new \stdClass;
		}
		$value->report_columns = $this->report_columns;
		$value->report_filters = $this->report_filters;
		$value->report_sorting = $this->report_sorting;
		$value->only_totals = $this->only_totals;
		$value->landscape= $this->landscape;
		$this->value = json_encode($value);
	}

	public function decodeValue()
	{
		$value = json_decode($this->value, true);
		$this->report_columns = $value['report_columns']??[];
		$this->report_filters = $value['report_filters']??[];
		$this->report_sorting = $value['report_sorting']??[];
		$this->only_totals = $value['only_totals']??false;
		$this->landscape= $value['landscape']??true;
	}

	protected function findColumn($attribute)
	{
		foreach( $this->report_columns as $rk => $coldef ) {
			if( $coldef['attribute'] == $attribute ) {
				return $coldef;
			}
		}
	}

	/**
	 * Transforms grid columns into report columns
	 */
	public function fixColumnDefinitions($model, $allColumns)
	{
		$columns = [];
		$tablename = str_replace(['{','}','%'], '', $model->tableName());
		foreach( $allColumns as $colname => $column ) {
// 			if( null != ($repcol = $this->findColumn($colname)) ) {
// 				$column = ArrayHelper::merge($column, $repcol);
// 			}
			if( !isset($column['contentOptions']) ) {
				$column['contentOptions'] = [];
			}
			if( !isset($column['headerOptions']) ) {
				$column['headerOptions'] = [];
			}
			if( !isset($column['footerOptions']) ) {
				$column['footerOptions'] = [];
			}
			$classes = explode(' ', $column['options']['class']??'');
			if( isset($column['format']) ) {
				$classes[] = 'reportview-' . $column['format'];
			}
			$column['contentOptions']['class'] = $column['headerOptions']['class']
				= $column['footerOptions']['class'] = trim(implode(' ', $classes));
			if( preg_match('/^(sum|avg|max|min):(.*)$/i', $colname, $matches) ) {
				if( empty($column['attribute']) ) {
					$column['attribute'] = $matches[2];
				}
				$column['columnSummaryFunc'] = $matches[1];
			} else {
				if( empty($column['attribute']) ) {
					$column['attribute'] = $colname;
				}
				$column['columnSummaryFunc'] = $column['summary']??'';
			}
			unset($column['summary']);
			$ta = $column['attribute'];
			// If the tablename of the column is this model, remove it
			if( ($dotpos=strpos($ta, '.')) !== FALSE ) {
				$t = substr($ta, 0, $dotpos);
				if( $t == $tablename ) {
					$a = substr($ta, $dotpos+1);
					$column['attribute'] = $a;
				}
			}
			if( empty($column['label']) ) {
				if( isset( $report_column['label'] ) ) {
					$column['label'] = $report_column['label'];
				} else {
					$column['label'] = $model->getAttributeLabel($column['attribute']);
				}
			}
			$columns[$colname] = $column;
		}
		return $columns;
	}

	/**
	 * Creates the data provider for the report grid.
	 * Sets the query, select, joins, orderBy, groupBy and filters
	 */
	public function dataProviderForReport($model, &$columns, $all_columns)
    {
        $query = new Query(); // Do not use $model->find(), as we want a query, not active records

        $provider = new ActiveDataProvider([
            'query' => $query->from($model->tableName()),
            'pagination' => [
				'pagesize' => $_GET['per-page']??10,
				'page' => $_GET['page']??0,
			],
		]);

		$tablename = $model->tableName();
		$orderby = [];
		foreach( $this->report_sorting as $kc => $sorting_def ) {
			$colname = $sorting_def['attribute']??$kc;
			if( !isset($columns[$colname]) ) {
				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$colname' not found in sorting");
				continue;
			}
			$column_def = $all_columns[$colname];
			$attribute = $column_def['attribute'];
			if( isset($column_def['calc']) ) {
				$orderby[] = new yii\db\Expression(strtr($attribute, [ '{tablename}' => $tablename ]));
			} else if ( is_int($attribute) || array_key_exists($attribute, $model->attributes ) ) {
				$orderby[] = $tablename .'.'.$attribute
					. ($sorting_def['asc']??SORT_ASC==SORT_ASC?' ASC':' DESC');
			} else if( ($dotpos = strpos($attribute, '.')) !== FALSE ) {
				$joins = [];
				list($tablename, $attribute, $alias) = static::addRelatedField($model, $attribute, $joins);
				$orderby[] = $tablename .'.'.$attribute
					. ($sorting_def['asc']??SORT_ASC==SORT_ASC?' ASC':' DESC');
			} else {
				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': order '$attribute' not found");
				continue;
			}
		}
		$provider->query->orderBy( join(',',$orderby) );
		$provider->sort = false;

		foreach( $this->report_filters as $kc => $filter_def ) {
			$colname = $filter_def['attribute'];
			if( !isset($all_columns[$colname]) ) {
				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$colname' of filter not found");
				continue;
			}
			$column_def = $all_columns[$colname];
			unset($filter_def['attribute']);
			if( isset($column_def['calc']) ) {
				$model->filterWhere($query,
					new \yii\db\Expression(strtr($all_columns[$colname]['attribute'], [ '{tablename}' => $tablename ])),
					$filter_def);
			} else if( isset($all_columns[$colname]) ) {
				$model->filterWhere($query, $all_columns[$colname]['attribute'], $filter_def);
			} else {
				$model->filterWhere($query, $colname, $filter_def);
			}
		}
		$this->addSelectToQuery($model, $columns, array_keys($this->report_filters), $query);
		return $provider;
	}


	static protected function addRelatedField($left_model, $attribute, &$joins)
	{
		$tablename = $alias = '';
		while( ($dotpos = strpos($attribute, '.')) !== FALSE ) {
			$relation_name = substr($attribute, 0, $dotpos);
			if( !empty($alias) ) { $alias .= "_"; }
			$alias .= $relation_name;
			$attribute = substr($attribute, $dotpos + 1);
			if( $relation_name == str_replace(['{','}','%'],'',$left_model->tableName() ) ) {
				$tablename = $relation_name;
				continue;
			}
			if( isset($left_model::$relations[$relation_name]) ) {
				$relation = $left_model::$relations[$relation_name];
				$tablename = $relation['relatedTablename'];
				// @todo if more than one, ¿add with an alias x1, x2...?
				if( !isset($joins[$tablename]) ) {
					$joins[$tablename] = $relation['join'];
				}
				$left_model = $relation['modelClass']::instance();
			} else {
				throw new \Exception($relation_name . ": relation not found in model " . $left_model::className() . " with relations " . join(',', array_keys($left_model::$relations)));
			}
		}
		$alias .= "_$attribute";
		return [ $tablename, $attribute, $alias ];
	}


	/**
	 * Adds related select and joins to dataproviders for reports
	 */
	public function addSelectToQuery($model, &$columns, $filters, &$query)
	{
		$joins = [];
		$groups = [];
		$selects = [];
		foreach( $columns as $kc => $column_def ) {
			$calc = !empty($column_def['calc']);
			if( $calc ) {
				unset($columns[$kc]['calc']);
			}
			if( !isset($column_def['attribute']) ) {
				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$kc' has no attribute");
				continue;
			}
			$attribute = $column_def['attribute'];
			$tablename = str_replace(['{','}','%'], '', $model->tableName() );
			$alias = null;
			if( $calc) {
				$alias = str_replace('.','_',$kc);
				$select_field = new yii\db\Expression(strtr($attribute, [ '{tablename}' => $tablename ]));
			} else if ( is_int($attribute) || array_key_exists($attribute, $model->attributes ) ) {
				$select_field = $tablename.'.'.$attribute;
			} else if( ($dotpos = strpos($attribute, '.')) !== FALSE ) {
				list($tablename, $attribute, $alias) = static::addRelatedField($model, $attribute, $joins);
				$select_field = $tablename.'.'.$attribute;
			}
			if( $alias != null ) {
 				$columns[$kc]['attribute'] = $alias;
			}
// 			if( !empty($column_def['columnSummaryFunc']) ) {
// 				$agg = $column_def['columnSummaryFunc'];
// 				$pks = $model->primaryKey();
// 				foreach( $pks as $pk ) {
// 					$groupby = $model->tableName() . ".$pk";
// 					if( !isset($groups[$groupby]) ) {
// 						$groups[$groupby] = $groupby;
// 					}
// 				}
// 				switch($agg) {
// 				case ReportView::F_COUNT:
// 					$f_agg = 'COUNT';
// 					break;
// 				case ReportView::F_SUM:
// 					$f_agg = 'SUM';
// 					break;
// 				case ReportView::F_MAX;
// 					$f_agg = 'MAX';
// 					break;
// 				case ReportView::F_MIN;
// 					$f_agg = 'MIN';
// 					break;
// 				case ReportView::F_AVG;
// 					$f_agg = 'AVERAGE';
// 					break;
// 				}
// 				$select_field = $f_agg."($select_field)";
// 			}
			if( $alias ) {
				$selects[$alias] = $select_field;
			} else {
				$selects[] = $select_field;
			}
		}
		$query->select($selects);
		foreach( $joins as $jk => $jv ) {
			$query->innerJoin($jk, $jv);
		}
		$query->groupBy($groups);
    }

	/**
	 * Gets only the columns used in this report
	 */
	public function reportColumns($allColumns)
	{
		$columns = [];
		foreach( $this->report_columns as $kc => $column ) {
			$colname = $column['attribute']??$kc;
			if( !isset($allColumns[$colname]) ) {
				$point_parts = explode('.',$colname);
				$last_part = array_pop($point_parts);
				if( in_array($last_part, ['sum','avg','count','distinct count', 'concat', 'distinct concat', 'max', 'min']) ) {
					$cn = $last_part . ":" . implode($point_parts, '.');
					if(isset($allColumns[$cn])) {
						$columns[$colname] = $allColumns[$cn];
					}
				}
			} else {
				$columns[$colname] = $allColumns[$colname];
			}
			if( isset($column['summary']) ) {
				$columns[$colname]['pageSummary'] = true;
				$columns[$colname]['pageSummaryFunc'] = $column['summary'];
			} else if( isset($columns[$colname]['summary']) ) {
				// la definición del campo ya tiene un summary
				$columns[$colname]['pageSummary'] = true;
				$columns[$colname]['pageSummaryFunc'] = $columns[$colname]['summary'];
				unset($columns[$colname]['summary']);
			}
		}
		foreach( $this->report_sorting as $kc => $sorting_def ) {
			$colname = $sorting_def['attribute']??$kc;
			if( !isset($columns[$colname]) ) {
				if( isset($allColumns[$colname]) ) {
					$columns[$colname] = $allColumns[$colname];
				} else {
					Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$colname' not found in sorting");
				}
			}
		}
		return $columns;
	}


	public function reportGroups(&$report_columns)
	{
		$groups = [];
		foreach( $this->report_sorting as $kc => $column ) {
			if( !empty($column['group']) ) {
				$colname = $column['attribute']??$kc;
				if( isset($report_columns[$colname]) ) {
					$rc = $report_columns[$colname];
					$groups[$colname] = [
						'column' => $rc['attribute'],
						'format' => $rc['label'] . ': {group_value}',
						'header' => $column['show_header']??true,
						'footer' => $column['show_footer']??true,
					];
					if( empty($column['show_column']) ) {
						$report_columns[$colname]['visible'] = false;
					}
				}
			}
		}
		return $groups;
	}

	/**
	 * Groups the available report columns by table and returns an array for a dropDownList
	 */
	public function columnsForDropDown($model, $columns, $titles)
	{
 		$dropdown_options = [];
		$modeltablename = str_replace(['{','}','%'], '', $model->tableName());
		foreach( $columns as $colname => $colattrs ) {
			list($tablename, $fieldname) = ModelInfoTrait::splitFieldName($colname);
			if( empty($tablename) ) {
				$tablename = $modeltablename;
			}
			$group = $titles[$tablename]??$tablename;
			$attr = $colattrs['attribute']??null;
			if( substr($colname, -11) == '.desc_short' ) {
				$title = 'descripción';
			} else if( substr($colname, -10) == '.desc_long' ) {
				$title = 'descripción larga';
			} else {
				$title = $group;
			}
			$dropdown_options[$group][$colname] = $colattrs['label'] . " ($title)";
		}
		return $dropdown_options;
	}



}
