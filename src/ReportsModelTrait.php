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
		$ret = parent::load($data, $scope);
		if( isset($data[$scope]['only_totals'] ) ) {
			$this->only_totals = boolval($data[$scope]['only_totals']);
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
		return $ret;
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
		$this->value = json_encode($value);
	}

	public function decodeValue()
	{
		$value = json_decode($this->value, true);
		$this->report_columns = $value['report_columns']??[];
		$this->report_filters = $value['report_filters']??[];
		$this->report_sorting = $value['report_sorting']??[];
		$this->only_totals = $value['only_totals']??false;
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
	public function dataProviderForReport($model, &$columns)
    {
        $query = new Query(); // Do not use $model->find(), as we want a query, not active records

        $provider = new ActiveDataProvider([
            'query' => $query->from($model->tableName()),
            'pagination' => [
				'pagesize' => $_GET['per-page']??10,
				'page' => $_GET['page']??0,
			],
		]);

		$orderby = [];
		foreach( $this->report_sorting as $sorting_def ) {
			$colname = $sorting_def['attribute'];
			if( !isset($columns[$colname]) ) {
				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$colname' not found in sorting");
				continue;
			}
			$column_def = $columns[$colname];
			$attribute = $column_def['attribute'];
			if ( is_int($attribute) || array_key_exists($attribute, $model->attributes ) ) {
				$tablename = $model->tableName();
			} else if( ($dotpos = strpos($attribute, '.')) !== FALSE ) {
				$joins = [];
				list($tablename, $attribute, $alias) = $model->addRelatedField($attribute, $joins);
				$sort_column = $tablename .'.' . $attribute;
			} else {
				throw new \Exception($attribute . ": attribute not found");
			}
			$orderby[] = $tablename .'.' . $attribute
				. ($sorting_def['asc']??SORT_ASC==SORT_ASC?' ASC':' DESC');
		}
		$provider->query->orderBy( implode(',', $orderby) );
		$provider->sort = false;

		foreach( $this->report_filters as $filter_def ) {
			$colname = $filter_def['attribute'];
			unset($filter_def['attribute']);
			if( isset($columns[$colname]) ) {
				$model->filterWhere($query, $columns[$colname]['attribute'], $filter_def);
			} else {
				$model->filterWhere($query, $colname, $filter_def);
			}
		}
		$this->addSelectToQuery($model, $columns, array_keys($this->report_filters), $query);
		return $provider;
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
			$had_dot = false;
			$attribute = $column_def['attribute'];
			$tablename = str_replace(['{','}','%'], '', $model->tableName() );
			$alias = null;
			if( substr($kc,0,6) === '.calc.' ) {
				$alias = str_replace('.','_',substr($kc,6));
				$select_field = new yii\db\Expression(strtr($attribute, [ '{tablename}' => $tablename ]));
			} else if ( is_int($attribute) || array_key_exists($attribute, $model->attributes ) ) {
				$select_field = $tablename.'.'.$attribute;
			} else if( ($dotpos = strpos($attribute, '.')) !== FALSE ) {
				$had_dot = true;
				list($tablename, $attribute, $alias) = $model->addRelatedField($attribute, $joins);
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
		foreach( $this->report_columns as $column ) {
			$colname = $column['attribute'];
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
		foreach( $this->report_sorting as $sorting_def ) {
			$colname = $sorting_def['attribute'];
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
		foreach( $this->report_sorting as $column ) {
			if( !empty($column['group']) ) {
				$colname = $column['attribute'];
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
		foreach( $columns as $colname => $col_attrs ) {
			list($tablename, $fieldname) = ModelSearchTrait::splitFieldName($colname);
			if( empty($tablename) ) {
				$tablename = $modeltablename;
			}
			$title = $titles[$tablename]??$tablename;
			$dropdown_options[$title][$colname] = $col_attrs['label'] . " ($title)";
		}
		return $dropdown_options;
	}



}
