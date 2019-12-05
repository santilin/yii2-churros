<?php
namespace santilin\churros;

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use yii\data\ActiveDataProvider;
use santilin\churros\ModelSearchTrait;

trait ReportsModelTrait
{
	public $report_columns = [];
	public $report_filters = [];
	public $report_sorting = [];

	public function getValue($var, $default)
	{
		$values = json_decode($this->value);
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
		$ret = parent::load($data, $formName);
		if( isset($data['report_columns']) ) {
			// The HTML form sends the data trasposed
			$this->report_columns = [];
			foreach( $data['report_columns']['name'] as $key => $value) {
				if( empty($value) ) {
					continue; // The column has not been specified
				}
				$this->report_columns[$value] = [
					'summary' => $data['report_columns']['summary'][$key],
					'label' => $data['report_columns']['label'][$key],
					'width' => $data['report_columns']['width'][$key]
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
				$this->report_sorting[$value] = [
					'asc' => isset($data['report_sorting']['asc'][$key])?$data['report_sorting']['asc'][$key] : SORT_ASC,
					'group' => isset($data['report_sorting']['group'][$key]) ? $data['report_sorting']['group'][$key] : false,
				];
			}
		}
		$searchScope = $this->model;
		if( substr($searchScope, -6) != "Search" ) {
			$searchScope .= "Search";
		}
		if( isset($data[$searchScope]['_adv_']) ) {
			$svalues = $data[$searchScope]['_adv_'];
			foreach($svalues as $key => $value ) {
				if( $value['lft'] != '' || $value['rgt'] != ''
					|| $value['op'] != '=' ) {
					$this->report_filters[$key] = $value;
				}
			}
		}
		return $ret;
	}

	public function encodeValue()
	{
		$value = json_decode($this->value);
		if( $value === null ) {
			$value = new \stdClass;
		}
		$value->report_columns = $this->report_columns;
		$value->report_filters = $this->report_filters;
		$value->report_sorting = $this->report_sorting;
		$this->value = json_encode($value);
	}

	public function decodeValue()
	{
		$value = json_decode($this->value, true); // objects as arrays
		$this->report_columns = isset($value['report_columns']) ? $value['report_columns'] : [];
		$this->report_filters = isset($value['report_filters']) ? $value['report_filters'] : [];
		$this->report_sorting = isset($value['report_sorting']) ? $value['report_sorting'] : [];
	}

	/**
	 * Returns Html code to add an advanced search field to a search form
	 * @param boolean $hidden Whether to include the general condition as a hidden input
	 */
	public function createSearchField($model, $attribute, $dropdown_values = null)
	{
		$ret = '';
		$scope = $model->formName();
		if( isset( $this->report_filters[$attribute] ) ) {
			$value = $this->report_filters[$attribute];
		} else {
			$value = [ 'op' => '', 'lft' => '', 'rgt' => ''];
		}
		if( !in_array($value['op'], ModelSearchTrait::$extra_operators) ) {
			$extra_visible = "display:none";
		} else {
			$extra_visible = '';
		}
		$attr_class = str_replace('.','_',$attribute);
		$ret .= "<div class='row'>";
		$ret .= "<div class='form-group'>";
		$ret .= "<div class='control-label col-sm-3'>";
		$ret .= Html::activeLabel($model, $attribute);
		$ret .= "</div>";

		$ret .= "<div class='control-form col-sm-2'>";
		$ret .= Html::dropDownList("${scope}[_adv_][$attribute][op]",
			$value['op'], ModelSearchTrait::$operators, [
			'id' => "drop-$attr_class", 'class' => 'search-dropdown form-control col-sm-2'] );
		$ret .= "</div>";
		$ret .= "<div class='control-form col-sm-4'>";
		if( is_array($dropdown_values) ) {
			$ret .= Html::dropDownList("${scope}[_adv_][$attribute][lft]",
			$value['lft'], $dropdown_values, [ 'class' => 'form-control col-sm-4']);
		} else {
			$ret .= Html::input('text', "${scope}[_adv_][$attribute][lft]",
			$value['lft'], [ 'class' => 'form-control col-sm-4']);
		}
		$ret .= "</div>";
		$ret .= "</div><!-- row -->";


		$ret .= "<div class='row gap10'>";
		$ret .= "<div style='$extra_visible' id='second-field-drop-$attr_class' >";
		$ret .= "<div class='control-form col-sm-2 col-sm-offset-3 text-right'>";
		$ret .= "y:";
		$ret .= "</div>";
		$ret .= "<div class='control-form col-sm-4'>";
		if( is_array($dropdown_values) ) {
			$ret .= Html::dropDownList("${scope}[_adv_][$attribute][rgt]",
			$value['rgt'], $dropdown_values, [ 'class' => 'form-control col-sm-4']);
		} else {
			$ret .= Html::input('text', "${scope}[_adv_][$attribute][rgt]",
				$value['rgt'], [ 'class' => 'form-control col-sm-4']);
		}
		$ret .= "</div>";
		$ret .= "</div><!-- row -->";
		$ret .= "</div>";
		$ret .= "</div>";
		return $ret;
	}

	/**
	 * Transforms kartik grid columns into report columns
	 */
	public function fixColumnDefinitions($model, $allColumns)
	{
		$columns = [];
		foreach( $allColumns as $colname => $grid_column ) {
			$column = [];
			if( isset($grid_column['hAlign']) ) {
				$column['hAlign'] = $grid_column['hAlign'];
			}
			if( preg_match('/^(sum|avg|max|min):(.*)$/i', $colname, $matches) ) {
				$column['attribute'] = $matches[2];
				$column['aggregate'] = $matches[1];
			} else {
				$column['attribute'] = $colname;
				$column['aggregate'] = '';
			}
			if( isset($grid_column['value']) ) {
				$column['value'] = $grid_column['value'];
			}
			$orig_title = '';
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
	public function dataProviderForReport($model, $columns)
    {
        $query = new Query(); // Do not use $model->find(), as we want a query, not active records

        $provider = new ActiveDataProvider([
            'query' => $query->from($model->tableName()),
            'pagination' => false,
        ]);

		$sort = [];
		foreach( $this->report_sorting as $colname => $sorting_column ) {
			$sort[str_replace(".", "_", $colname)] = $sorting_column['asc'];
		}
		$provider->sort->attributes = [ 'default' =>  [ 'asc' => $sort ]];
		$provider->sort->defaultOrder = [ 'default' => SORT_ASC ];

		foreach( $this->report_filters as $colname => $value ) {
 			$model->filterWhere($query, $colname, $value);
		}
		$this->addSelectToQuery($model, $columns,
			array_keys($this->report_filters), $query);
		return $provider;
	}

	/**
	 * Adds related select and joins to dataproviders for reports
	 */
	public function addSelectToQuery($model, $columns, $filters, &$query)
	{
		$joins = [];
		$groups = [];
		$selects = [];
		foreach( $columns as $column_def ) {
			$attribute = $column_def['attribute'];
			if ( is_int($attribute) || array_key_exists($attribute, $model->attributes ) ) {
				$tablename = $model->tableName();
				$alias = $attribute;
			} else if( ($dotpos = strpos($attribute, '.')) !== FALSE ) {
				list($tablename, $attribute, $alias) = $model->addRelatedField($attribute, $joins);
			} else {
				throw new \Exception($attribute);
			}
			if( !empty($column_def['aggregate']) ) {
				$agg = $column_def['aggregate'];
				$alias = $agg . "_" . $alias;
				$groupby = $tablename . ".id";
				if( !isset($groups[$groupby]) ) {
					$groups[$groupby] = $groupby;
				}
				$select_field = $agg."(". $tablename . "." . $attribute . ") AS $alias";
			} else {
				$select_field = $tablename . "." . $attribute;
				if( $alias != $attribute )
					$select_field .= " AS $alias";
			}
			$selects[] = $select_field;
		}
		$query->select($selects);
		foreach( $joins as $jk => $jv ) {
			$query->leftJoin($jk, $jv);
		}
		$query->groupBy($groups);
    }

	/**
	 * Extracts the kartik columns for this report
	 */
	public function gridColumns($allColumns)
	{
		$columns = [];
		foreach( $this->report_columns as $colname => $col_attrs ) {
			if( !isset($allColumns[$colname]) ) {
				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$colname' not found");
				continue;
			}
			$new_column = $allColumns[$colname];
			if( isset($this->report_columns[$colname]['summary'])
				&& $this->report_columns[$colname]['summary'] != '' )  {
				$new_column['pageSummary'] = true;
				$new_column['pageSummaryFunc'] = $this->report_columns[$colname]['summary'];
			}
			if( !empty($new_column['aggregate']) ) {
				$colname = $new_column['aggregate'] . "_" . $new_column['attribute'];
			}
			unset($new_column['aggregate'], $new_column['summary']);
			$columns[$colname] = $new_column;
		}
		$groups = [];
		foreach( $this->report_sorting as $colname => $sorting ) {
			if( isset($sorting['group']) && $sorting['group'] == true ) {
				if( !isset($columns[$colname]) ) {
					if( !isset($allColumns[$colname]) ) {
						Yii::$app->session->addFlash("error", "Report '" . $this->name . "': group column '$colname' not found");
					} else {
						$groups[$colname] = $allColumns[$colname];
						unset($groups[$colname]['aggregate'], $groups[$colname]['summary']);
					}
				}
				$columns[$colname]['group'] = $sorting['group'];
			}
		}
		if( count($groups) ) {
			return $groups + $columns;
		} else {
			return $columns;
		}
	}


	/**
	 * Gets only the report columns
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
		}
		foreach( $this->report_sorting as $colname => $sorting ) {
			if( !isset($columns[$colname]) ) {
				$columns[$colname] = $allColumns[$colname];
			}
		}
		return $columns;
	}


	public function reportGroups($report_columns)
	{
		$groups = [];
		foreach( $this->report_sorting as $colname => $column ) {
			if( isset($column['group']) && $column['group'] == true ) {
				if( isset($report_columns[$colname]) ) {
					$rc = $report_columns[$colname];
					$groups[$rc['attribute']] = [
						'column' => str_replace('.','_',$rc['attribute']),
						'format' => $rc['label'] . ': {group_value}',
						'header' => true,
						'footer' => true
					];
				}
			}
		}
		return $groups;
	}

	/**
	 * Groups the available report columns by table and returns an array for a dropDownList
	 */
	public function columnsForDropDown($model, $columns)
	{
		$dropdown_options = [];
		$title = $model->getModelInfo('title');
		foreach( $columns as $colname => $col_attrs ) {
			list($modelname, $fieldname) = ModelSearchTrait::splitFieldName($col_attrs['attribute']);
			if( empty($modelname) ) {
				$dropdown_options[$title][$colname] = $col_attrs['label'];
			} else {
				$modelname = ucfirst($modelname);
				$dropdown_options[$modelname][$colname] = $col_attrs['label'] . " ($modelname)";
			}
		}
		return $dropdown_options;
	}


}