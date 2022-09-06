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
						'op' => $svalues['op'][$key]
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
		$value->report_columns = [];
		foreach( $this->report_columns as $coldef ) {
			$value->report_columns += [$coldef];
		}
		$value->report_filters = [];
		foreach( $this->report_filters as $filterdef ) {
			$value->report_filters += [$filterdef];
		}
		foreach($value->report_filters as $key => $v ) {
			if( is_array($v) && isset($v['lft']) && $v['lft'] === '' ) {
				unset($value->report_filters[$key]);
			}
		}
		$value->report_sorting = [];
		foreach( $this->report_sorting as $sortdef ) {
			$value->report_sorting += [$sortdef];
		}
		$value->only_totals = $this->only_totals;
		$this->value = json_encode($value);
	}

	public function decodeValue()
	{
		$value = json_decode($this->value, true);
		$this->report_columns = [];
		if( $value && $value['report_columns'] ) {
			foreach( $value['report_columns'] as $coldef ) {
				$this->report_columns += [$coldef];
			}
		}
		$this->report_filters = [];
		if( $value && $value['report_filters'] ) {
			foreach( $value['report_filters'] as $filterdef ) {
				$this->report_filters += [$filterdef];
			}
		}
		$this->report_sorting = [];
		if( $value && $value['report_sorting'] ) {
			foreach( $value['report_sorting'] as $sortdef ) {
				$this->report_sorting += [$sortdef];
			}
		}
		$this->only_totals = $value['only_totals']??false;
	}

	/**
	 * Transforms kartik grid columns into report columns
	 */
	public function fixColumnDefinitions($model, $allColumns)
	{
		$columns = [];
		$tablename = str_replace(['{','}','%'], '', $model->tableName());
		foreach( $allColumns as $colname => $grid_column ) {
			$column = [];
			if( isset($grid_column['hAlign']) ) {
				$column['hAlign'] = $grid_column['hAlign'];
			}
			if( isset($grid_column['format']) ) {
				$column['format'] = $grid_column['format'];
			}
			if( preg_match('/^(sum|avg|max|min):(.*)$/i', $colname, $matches) ) {
				$column['attribute'] = $matches[2];
				$column['columnSummaryFunc'] = $matches[1];
			} else {
				$column['columnSummaryFunc'] = '';
				$column['attribute'] = $colname;
			}
			$ta = $column['attribute'];
			// If the tablename of the column is this model, remove it
			if( ($dotpos=strpos($ta, '.')) !== FALSE ) {
				$t = substr($ta, 0, $dotpos);
				if( $t == $tablename ) {
					$a = substr($ta, $dotpos+1);
					$column['attribute'] = $a;
				}
			}
			if( isset($grid_column['value']) ) {
				$column['value'] = $grid_column['value'];
			}
			if( isset($this->report_columns[$colname]) ) {
				$column = ArrayHelper::merge($column, $this->report_columns[$colname]);
			}
			if( empty($column['label']) ) {
				if( isset( $grid_column['label'] ) ) {
					$column['label'] = $grid_column['label'];
				} else {
					$column['label'] = $model->getAttributeLabel($column['attribute']);
				}
			}
			$columns[$colname] = $column;
		}
		return $columns;
	}

	/**
	 * Creates the data provider for the grid.
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
		foreach( $this->report_sorting as $colname => $sorting_column ) {
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
				. ($sorting_column['asc']??SORT_ASC==SORT_ASC?' ASC':' DESC');
		}
		$provider->query->orderBy( implode(',', $orderby) );
		$provider->sort = false;

		foreach( $this->report_filters as $colname => $value ) {
			if( isset($columns[$colname]) ) {
				$model->filterWhere($query, $columns[$colname]['attribute'], $value);
			} else {
				$model->filterWhere($query, $colname, $value);
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
			if ( is_int($attribute) || array_key_exists($attribute, $model->attributes ) ) {
				$tablename = str_replace(['{','}','%'], '', $model->tableName() );
				$alias = $attribute;
			} else if( ($dotpos = strpos($attribute, '.')) !== FALSE ) {
				$had_dot = true;
				list($tablename, $attribute, $alias) = $model->addRelatedField($attribute, $joins);
			} else {
				throw new \Exception($attribute . ': attribute not found in addSelectToQuery');
			}
			if( !empty($column_def['columnSummaryFunc']) ) {
				$agg = $column_def['columnSummaryFunc'];
				$pks = $model->primaryKey();
				foreach( $pks as $pk ) {
					$groupby = $model->tableName() . ".$pk";
					if( !isset($groups[$groupby]) ) {
						$groups[$groupby] = $groupby;
					}
				}
				switch($agg) {
				case ReportView::F_COUNT:
					$f_agg = 'COUNT';
					break;
				case ReportView::F_SUM:
					$f_agg = 'SUM';
					break;
				case ReportView::F_MAX;
					$f_agg = 'MAX';
					break;
				case ReportView::F_MIN;
					$f_agg = 'MIN';
					break;
				case ReportView::F_AVG;
					$f_agg = 'AVERAGE';
					break;
				}
				$select_field = $f_agg."(". $tablename . "." . $attribute . ") AS $alias";
				$columns[$kc]['attribute'] = str_replace('.','_',$select_field);
			} else {
				$select_field = $tablename . "." . $attribute;
				if( $had_dot ) {
					$columns[$kc]['attribute'] = str_replace('.','_',$select_field);
				}
				if( $alias != $attribute )
					$select_field .= " AS $alias";
			}
			$selects[] = $select_field;
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
		foreach( $this->report_columns as $colname => $column ) {
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
			if( isset($columns[$colname]['summary']) ) {
				$columns[$colname]['pageSummary'] = true;
				$columns[$colname]['pageSummaryFunc'] = $columns[$colname]['summary'];
				unset($columns[$colname]['summary']);
			}
		}
		foreach( $this->report_sorting as $colname => $sorting ) {
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
		foreach( $this->report_sorting as $colname => $column ) {
			if( isset($column['group']) && $column['group'] == true ) {
				if( isset($report_columns[$colname]) ) {
					$rc = $report_columns[$colname];
					$repl_colname = str_replace('.','_',$colname);
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
