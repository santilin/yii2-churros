<?php
namespace santilin\churros;

use yii;
use yii\helpers\{Html,ArrayHelper};
use yii\db\Query;
use yii\data\ActiveDataProvider;
use santilin\churros\ModelSearchTrait;
use santilin\churros\widgets\grid\ReportView;

trait ReportsModelTrait
{
	public $report_columns = [];
	public $report_filters = [];
	public $report_sorting = [];
	public $only_totals = false;
	public $landscape = false;

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
		if( !parent::load($data, $formName) ) {
			return false;
		}
		if( isset($data[$scope]['only_totals'] ) ) {
			$this->only_totals = boolval($data[$scope]['only_totals']);
		}
		if( isset($data[$scope]['landscape'] ) ) {
			$this->landscape = boolval($data[$scope]['landscape']);
		}
		if( isset($data['report_columns']) ) {
			unset($data['report_columns']['_index_']);
			$this->report_columns = $data['report_columns'];
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
		if( substr($searchScope, -7) != "_Search" ) {
			$searchScope .= "_Search";
		}
		if( isset($data[$searchScope]) ) {
			unset($data[$searchScope]['_index_']);
			$this->report_filters = $data[$searchScope];
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
		$this->landscape= $value['landscape']??false;
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
				$column['pageSummaryFunc'] = $matches[1];
			} else {
				if( empty($column['attribute']) ) {
					$column['attribute'] = $colname;
				}
				$column['pageSummaryFunc'] = $column['summary']??'';
			}
			unset($column['summary']);
			if( empty($column['label']) ) {
				$a = $column['attribute'];
				if( ($dotpos=strpos($a, '.')) !== FALSE ) {
					$t = substr($a, 0, $dotpos);
					if( $t == $tablename ) {
						$a = substr($a, $dotpos+1);
					}
				}
				$column['label'] = $model->getAttributeLabel($a);
			}
			$columns[$colname] = $column;
		}
		return $columns;
	}

	/**
	 * Creates the data provider for the report grid.
	 * Sets the query, select, joins, orderBy, groupBy and filters
	 * @param ModelInfoTrait $model the data model for the report
	 * @param array $columns the columns included in the report definition
	 * @param array $all_columns all the columns available for the rerpot definition
	 */
	public function dataProviderForReport($model, array &$columns, array $all_columns)
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

		$filter_columns = [];
		foreach( $this->report_filters as $filter_def ) {
			$colname = $filter_def['name'];
			$filter_columns[] = $colname;
			if( !isset($all_columns[$colname]) ) {
				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$colname' of filter not found");
				continue;
			}
			$column_def = $all_columns[$colname];
			unset($filter_def['name']);
			if( $colname != $column_def['attribute']) {
				$model->filterWhere($query,
					new \yii\db\Expression(strtr($column_def['attribute'],
						[ '{tablename}' => $tablename ])), $filter_def);
			} else {
				$attribute = static::removeTableName($column_def['attribute']);
				$model->filterWhere($query, $attribute, $filter_def);
			}
		}
		$this->addSelectToQuery($model, $columns, $filter_columns, $query);
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
			if( !isset($column_def['attribute']) ) {
 				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$kc' has no attribute in addSelectToQuery");
 				continue;
			}
			$fldname = static::removeTableName($column_def['name']);
			$attribute = $column_def['attribute'];
			$tablename = str_replace(['{','}','%'], '', $model->tableName() );
			if( ($dotpos = strpos($fldname, '.')) !== FALSE ) {
				static::addRelatedField($model, $fldname, $joins);
				$select_field = $attribute;
			} else {
				$alias = $select_field = $attribute;
			}
			$alias = strtr($select_field, [
				'.' => '_',
				'(' => '_', ')' => '_'
			]);
			if ($alias != $select_field) {
				$selects[$alias] = $select_field;
			} else {
				$selects[] = $select_field;
 			}
 			$columns[$kc]['attribute'] = $alias;
		}
		$query->select($selects);
		foreach( $joins as $jk => $jv ) {
			$query->innerJoin($jk, $jv);
		}
		$query->groupBy($groups);
    }

	static protected function removeFirstTableName(string $fullname): string
	{
		$parts = explode('.', $fullname);
		if ( count($parts)>2 ) {
			return implode('.', array_slice($parts,1));
		} else {
			return $fullname;
		}
	}

	static protected function removeTableName(string $fullname): string
	{
		$parts = explode('.', $fullname);
		if ( count($parts)>1 ) {
			return implode('.', array_slice($parts,1));
		} else {
			return $fullname;
		}
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
	 * Gets only the columns used in this report
	 */
	public function extractReportColumns($allColumns)
	{
		$columns = [];
		foreach( $this->report_columns as $column_def ) {
			$colname = $column_def['name'];
			if( !isset($allColumns[$colname]) ) {
 				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$colname' does not exist in extractReportColumns");
 				continue;
			}
			$column_to_add = array_merge($allColumns[$colname], $column_def);
			if( !isset($column_to_add['attribute']) ) {
				$column_to_add['attribute'] = static::removeFirstTableName($column_to_add['attribute']);
			} else {
				$column_to_add['attribute'] = static::removeFirstTableName($colname);
			}
			if( !empty($column_to_add['pre_summary']) ) {
				$pre_sum_func = substr($column_to_add['pre_summary'],2);
				$column_to_add['attribute'] = "$pre_sum_func({$column_to_add['attribute']})";
			}
			unset($column_to_add['pre_summary']);
			if( isset($column_to_add['summary']) ) {
				$column_to_add['pageSummaryFunc'] = $column_to_add['summary'];
				unset($column_to_add['summary']);
			}
			$columns[$colname] = $column_to_add;
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
						'format' => !empty($rc['label']) ? ($rc['label'] . ': {group_value}') : '{group_value}',
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
			if ($tablename != $modeltablename) {
				$love = true;
			}
			$group = $titles[$tablename]??$tablename;
			$attr = $colattrs['attribute']??null;
			$title = '';
			if( substr($colname, -11) == '.desc_short' ) {
				$title = ' (descripción)';
			} else if( substr($colname, -10) == '.desc_long' ) {
				$title = ' (descripción larga)';
// 			} else {
// 				if ($modeltablename != $tablename) {
// 					$title = " ($group)";
// 				} else {
// 					$title = '';
// 				}
			}
			$dropdown_options[$group][$colname] = $colattrs['label'] . $title;
		}
		return $dropdown_options;
	}

}
