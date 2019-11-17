<?php namespace santilin\churros;

use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\base\InvalidArgumentException;
use kartik\grid\GridView;

/**
 * Eases the definition of filters and sorts in grids for search models
 *
 * Creates the properties that hold the related filter values.
 * Extracts the sort and where properties from the related search model data provider
 * @todo Extract the rules from the related model
 */
trait ModelSearchTrait
{
	protected $related_properties = [];
	private $dynamic_rules = [];
	static private $operators = [
			'=' => '=',
			'===' => 'Exactamente', // Distinguish = (in grid filter) from === in search form
			'<>' => '<>',
			'LIKE' => 'Contiene', 'NOT LIKE' => 'No contiene',
			'>' => '>', '<' => '<',
			'>=' => '>=', '<=' => '<=',
			'BETWEEN' => 'entre', 'NOT BETWEEN' => 'no entre' ];
	static private $extra_operators = [
			'BETWEEN', 'NOT BETWEEN' ];

    public function setAttributes($values, $safeOnly = true)
    {
		foreach( $values as $attribute => $value ) {
			if (!array_key_exists($attribute, $this->attributes) && !is_array($value) ) {
				$this->related_properties[$attribute] = $value;
				unset($values[$attribute]);
			}
		}
		return parent::setAttributes($values, $safeOnly);
    }

    public function __get($name)
    {
		if( isset($this->related_properties[$name]) ) {
			return $this->related_properties[$name];
		} else {
			try {
				return parent::__get($name);
			} catch( \yii\base\UnknownPropertyException $e) {
				return ArrayHelper::getValue($this, $name);
			}
		}
	}

	/**
	 * Set the related properties values from params
	 */
    public function __set($name, $value)
    {
		try {
			return parent::__set($name, $value);
		} catch (\yii\base\UnknownPropertyException $e) {
			$this->related_properties[$name] = $value;
		}
	}

	/**
	 * Adds related sorts and filters to dataproviders
	*/
    public function addSafeRules($gridColumns)
    {
		foreach( $gridColumns as $attribute => $colum_def ) {
			if ( is_int($attribute) || array_key_exists($attribute, $this->attributes ) ) {
				continue;
			}
			$this->dynamic_rules[] = [[$attribute], 'safe'];
		}
    }


	/**
	 * Adds related sorts and filters to dataproviders for grids
	*/
    public function addColumnsSortsFiltersToProvider($gridColumns, &$provider)
    {
		foreach( $gridColumns as $attribute => $colum_def ) {
			if ( is_int($attribute) || array_key_exists($attribute, $this->attributes ) ) {
				continue;
			}
			if( strpos($attribute, '.') === FALSE ) {
				$relation_name = $attribute;
				$fldname = '';
			} else {
				list($relation_name, $fldname) = self::splitFieldName($attribute);
			}
			if (isset(self::$relations[$relation_name]) ) {
				$related_model_class = self::$relations[$relation_name]['modelClass'];
				$v = ArrayHelper::getValue($this, $attribute);
// 				$filter_set = false;
				$table_alias = "as_$relation_name"; /// @todo check that the join is added only once
				$provider->query->joinWith("$relation_name $table_alias");
				if ($fldname == '' ) { /// @todo junction tables
					list($code_field, $desc_field) = $related_model_class::getCodeDescFields();
					if( $desc_field != '' && $code_field != '' ) {
// 						$provider->query->andFilterWhere(['or',
// 							[ 'LIKE', "$table_alias.$desc_field", $v ],
// 							[ 'LIKE', "$table_alias.$code_field", $v ]
// 						]);
// 						$filter_set = true;
						$fldname = $code_field;
					}
				}
// 				if (!$filter_set) {
// 					$provider->query->andFilterWhere(
// 						['LIKE', "$table_alias.$fldname", $v]);
// 				}
				if (!isset($provider->sort->attributes[$attribute])) {
					$related_model_search_class = $related_model_class . "Search";
					if( class_exists($related_model_search_class) ) {
						// Set orders from the related search model
						$related_model = new $related_model_search_class;
						$related_model_provider = $related_model->search(
							[ $related_model->formName() =>
								[ $fldname => $v]
							]);
						if (isset( $related_model_provider->sort->attributes[$fldname]) ) {
							$related_sort = $related_model_provider->sort->attributes[$fldname];
							$new_related_sort = [ 'label' => $related_sort['label']];
							unset($related_sort['label']);
							foreach( $related_sort as $asc_desc => $sort_def) {
								foreach( $sort_def as $key => $value ) {
									$new_related_sort[$asc_desc]
									= [ $table_alias.".".$key => $value ];
								}
							}
							$provider->sort->attributes[$attribute] = $new_related_sort ;
						}
					}
				}
			}
		}
    }

    // Advanced search with operators
	protected function makeSearchParam($values)
	{
		if( $values['op'] == '=' ) {
			return $values['lft'];
		} else {
			return json_encode($values);
		}
	}

	protected function filterWhere(&$query, $name, $value)
	{
		if( $value === null || $value === '' ) {
			return;
		}
		// addColumnsSortsFiltersToProvider adds the join tables with AS `as_xxxxxxx`
		if( strpos($name, '.') === FALSE ) {
			$name = $this->tableName() . '.' . $name;
		} else {
			list($relation, $fldname) = self::splitFieldName($name);
			if( isset(self::$relations[$relation]) ) {
				$name = self::$relations[$relation]['relatedTablename'] . ".$fldname";
			} else {
				throw new InvalidArgumentException($relation . ": relation not found in model " . self::class);
			}
		}
		if( is_string($value) && substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
			$value = json_decode($value, true);
		}
		if( isset($value['lft']) ) {
			if( $value['lft'] == null ) {
				return;
			}
			switch( $value['op'] ) {
				case "===":
				case "=":
					$query->andWhere([$name => $value['lft']]);
					break;
				case "<>":
				case ">=":
				case "<=":
				case ">":
				case "<":
				case "NOT LIKE":
				case "LIKE":
					$query->andWhere([ $value['op'], $name,
						$value['lft'] ]);
						break;
				case "BETWEEN":
				case "NOT BETWEEN":
					$query->andWhere([ $value['op'], $name,
						$value['lft'], $value['rgt'] ]);
					break;
			}
		} else if( is_numeric($value) && !is_string($value) ) {
			$query->andWhere([ $name => $value ]);
		} else {
			$query->andWhere([ 'like', $name, $value ]);
		}
	}

	protected function filterWhereRelated(&$query, $name, $value)
	{
		if( $value === null || $value === '' ) {
			return;
		}
		if( strpos($name, '.') === FALSE ) {
			$relation_name = $name;
			$attribute = '';
		} else {
			list($relation_name, $attribute) = self::splitFieldName($name);
		}
		if (isset(self::$relations[$relation_name]) ) {
			$related_model_class = self::$relations[$relation_name]['modelClass'];
			$filter_set = false;
			$table_alias = "as_$relation_name"; /// @todo check that the join is added only once
			$query->joinWith("$relation_name $table_alias");
			if ($attribute == '' ) {
				list($code_field, $desc_field) = $related_model_class::getCodeDescFields();
				if( $desc_field != '' && $code_field != '' ) {
					$query->andFilterWhere(['or',
						[ 'LIKE', "$table_alias.$desc_field", $value ],
						[ 'LIKE', "$table_alias.$code_field", $value ]
					]);
					$filter_set = true;
				}
			}
			if (!$filter_set) {
				if( $attribute == 'id' && intval($value) == $value) {
					$query->andFilterWhere( ["$table_alias.$attribute" => intval($value)]);
				} else {
					$query->andFilterWhere( ['LIKE', "$table_alias.$attribute", $value]);
				}
			}
		} else {
			throw new InvalidArgumentException($relation . ": relation not found in model " . self::class);
		}
	}

	/**
	 * Loads the advanced search form data
	 */
	public function load($params, $formName = null)
	{
		if( !isset($params['_pjax']) ) {
			// join search form params
			$scope = $formName === null ? $this->formName() : $formName;
			$newparams = [];
			parent::load($params, $formName);
			if( isset($params[$scope]['_adv_']) ) {
				foreach( $params[$scope]['_adv_'] as $name => $values) {
					if( isset($values['lft'])  && !empty($values['lft']) ) {
						$newparams[$name] = $this->makeSearchParam($values);
					}
				}
			}
			return parent::load([ $scope => $newparams], $formName);
		}
		return parent::load($params, $formName);
	}

	/**
	 * Returns Html code to add an advanced search field to a search form
	 */
	static public function searchField($model, $attribute)
	{
		$ret = '';
		$scope = $model->formName();
		$value = $model->$attribute;
		if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
			$value = json_decode($value, true);
			if( !isset($value['lft']) ) {
				$value = [ 'op' => '', 'lft' => '', 'rgt' => ''];
			}
		} else {
			$value = ['op' => '', 'lft' => $value, 'rgt' => ''];
		}
		if( !in_array($value['op'], self::$extra_operators) ) {
			$extra_visible = "display:none";
		} else {
			$extra_visible = '';
		}
		$ret .= "<div class='row'>";
		$ret .= "<div class='form-group'>";
		$ret .= "<div class='control-label col-sm-3'>";
		$ret .= Html::activeLabel($model, $attribute);
		$ret .= "</div>";

		$ret .= "<div class='control-form col-sm-2'>";
		$ret .= Html::dropDownList("${scope}[_adv_][$attribute][op]",
			$value['op'], self::$operators, [
			'id' => "drop-$attribute", 'class' => 'search-dropdown form-control col-sm-2'] );
		$ret .= "</div>";

		$ret .= "<div class='control-form col-sm-4'>";
		$ret .= Html::input('text', "${scope}[_adv_][$attribute][lft]",
			$value['lft'], [ 'class' => 'form-control col-sm-4']);
		$ret .= "</div>";
		$ret .= "</div><!-- row -->";


		$ret .= "<div class='row gap10'>";
		$ret .= "<div id='second-field-drop-$attribute' style='$extra_visible'>";
		$ret .= "<div class='control-form col-sm-2 col-sm-offset-3 text-right'>";
		$ret .= "y:";
		$ret .= "</div>";
		$ret .= "<div class='control-form col-sm-4'>";
		$ret .= Html::input('text', "${scope}[_adv_][$attribute][rgt]",
			$value['rgt'], [ 'class' => 'form-control col-sm-4']);
		$ret .= "</div>";
		$ret .= "</div><!-- row -->";
		$ret .= "</div>";
		$ret .= Html::activeHiddenInput($model, $attribute);
		$ret .= "</div>";
		return $ret;
	}

	/**
	 * Returns Html code to add an advanced search field to a search form
	 * @param boolean $hidden Whether to include the general condition as a hidden input
	 */
	static public function searchFieldForReport($report, $model, $attribute, $dropdown_values = null)
	{
		$ret = '';
		$scope = $model->formName();
		if( isset( $report->report_filters[$attribute] ) ) {
			$value = $report->report_filters[$attribute];
		} else {
			$value = [ 'op' => '', 'lft' => '', 'rgt' => ''];
		}
		if( !in_array($value['op'], self::$extra_operators) ) {
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
			$value['op'], self::$operators, [
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

	static public $searchFormJS = <<<JS
$('.search-dropdown').change(function() {
	let value= $(this).val();
	console.log('#second-field-' + this.id);
	console.log($('#second-field-' + this.id).html());
	if( value == 'BETWEEN' || value == 'NOT BETWEEN' ) {
		$('#second-field-' + this.id).show(200);
	} else {
		$('#second-field-' + this.id).hide(200);
	}
});
JS;


	/**
	 * Extracts the kartik columns for this report
	 */
	public function gridColumns($report, $allColumns)
	{
		$columns = [];
		foreach( $report->report_columns as $colname => $col_attrs ) {
			if( !isset($allColumns[$colname]) ) {
				Yii::$app->session->addFlash("error", "Report '" . $report->name . "': column '$colname' not found");
				continue;
			}
			$new_column = $allColumns[$colname];
			if( isset($report->report_columns[$colname]['summary'])
				&& $report->report_columns[$colname]['summary'] != '' )  {
				$new_column['pageSummary'] = true;
				$new_column['pageSummaryFunc'] = $report->report_columns[$colname]['summary'];
			}
			if( !empty($new_column['aggregate']) ) {
				$colname = $new_column['aggregate'] . "_" . $new_column['attribute'];
			}
			unset($new_column['aggregate'], $new_column['summary']);
			$columns[$colname] = $new_column;
		}
		$groups = [];
		foreach( $report->report_sorting as $colname => $sorting ) {
			if( isset($sorting['group']) && $sorting['group'] == true ) {
				if( !isset($columns[$colname]) ) {
					if( !isset($allColumns[$colname]) ) {
						Yii::$app->session->addFlash("error", "Report '" . $report->name . "': group column '$colname' not found");
					} else {
						$groups[$colname] = $allColumns[$colname];
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
	public function reportColumns($report, $allColumns)
	{
		$columns = [];
		foreach( $report->report_columns as $colname => $column ) {
			$columns[$colname] = $allColumns[$colname];
		}
		return $columns;
	}


	/**
	 * Transforms kartik grid columns into report columns
	 */
	public function fixColumnDefinitions($report, $allColumns)
	{
		$columns = [];
		foreach( $allColumns as $colname => $grid_column ) {
			$column = [];
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
			if( isset($report->report_columns[$colname]) ) {
				$column = ArrayHelper::merge($column, $report->report_columns[$colname]);
			}
			if( empty($column['label']) ) {
				if( isset( $grid_column['label'] ) ) {
					$column['label'] = $grid_column['label'];
				} else {
					$column['label'] = $this->getAttributeLabel($column['attribute']);
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
	public function dataProviderForReport($report, $columns)
    {
        $query = new yii\db\Query(); // self::find();

        $provider = new ActiveDataProvider([
            'query' => $query->from(self::tableName()),
            'pagination' => false,
        ]);

		$sort = [];
		foreach( $report->report_sorting as $colname => $sorting_column ) {
			$sort[str_replace(".", "_", $colname)] = $sorting_column['asc'];
		}
		$provider->sort->attributes = [ 'default' =>  [ 'asc' => $sort ]];
		$provider->sort->defaultOrder = [ 'default' => SORT_ASC ];

		foreach( $report->report_filters as $colname => $value ) {
 			$this->filterWhere($query, $colname, $value);
		}
		$this->addSelectToReportQuery(
			$columns, array_keys($report->report_filters), $query);
		return $provider;
	}

	public function addRelatedField($attribute, &$joins)
	{
		$alias = '';
		$left_model = $this;
		while( ($dotpos = strpos($attribute, '.')) !== FALSE ) {
			$relation_name = substr($attribute, 0, $dotpos);
			if( !empty($alias) ) { $alias .= "_"; }
			$alias .= $relation_name;
			$attribute = substr($attribute, $dotpos + 1);
			if( isset($left_model::$relations[$relation_name]) ) {
				$relation = $left_model::$relations[$relation_name];
				$tablename = $relation['relatedTablename'];
				if( !isset($joins[$relation['relatedTablename']]) ) {
					$joins[$relation['relatedTablename']] = $relation['join'];
				}
				$left_model = new $relation['modelClass'];
			} else {
				throw new \Exception($relation_name . ": relation not found in model " . $left_model->className() . " with relations " . join(',', array_keys($left_model::$relations)));
			}
		}
		$alias .= "_$attribute";
		return [ $tablename, $attribute, $alias ];
	}

	/**
	 * Adds related select and joins to dataproviders for reports
	 */
	public function addSelectToReportQuery($columns, $filters, &$query)
	{
		$joins = [];
		$groups = [];
		$selects = [];
		foreach( $columns as $column_def ) {
			$attribute = $column_def['attribute'];
			if ( is_int($attribute) || array_key_exists($attribute, $this->attributes ) ) {
				$tablename = self::tableName();
				$alias = $attribute;
			} else if( ($dotpos = strpos($attribute, '.')) !== FALSE ) {
				list($tablename, $attribute, $alias) = $this->addRelatedField($attribute, $joins);
			} else {
				throw \Exception($attribute);
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
	 * Groups the available report columns by table and returns an array for a dropDownList
	 */
	public function columnsForDropDown($report, $columns)
	{
		$dropdown_options = [];
		$base_model = $report->getValue('model', '.');
		foreach( $columns as $colname => $col_attrs ) {
			list($model, $field) = self::splitFieldName($col_attrs['attribute']);
			if( empty($model) ) {
				$dropdown_options[$base_model][$colname] = $col_attrs['label'];
			} else {
				$model = ucfirst($model);
				$dropdown_options[$model][$colname] = $col_attrs['label'] . " ($model)";
			}
		}
		return $dropdown_options;
	}

	static public function groupsFromColumns($gridcolumns, $allColumns)
	{
		$groups = [];
		foreach( $gridcolumns as $kc => $column ) {
			if( isset($column['group']) && $column['group'] !== '' ) {
				if( !isset($allColumns[$kc]) ) {
					\Yii::warning("Group column $kc not defined in all the columns for this report");
					continue;
				}
				$title = $allColumns[$kc]['label'];
				$groups[$column['attribute']] = [
					'column' => $column['attribute'],
					'format' => $title . ': {group_value}',
					'header' => true,
					'footer' => true
				];
			}
		}
		return $groups;
	}

    static public function splitFieldName($fieldname)
    {
		if( ($dotpos = strpos($fieldname, '.')) !== FALSE ) {
			$fldname = substr($fieldname, $dotpos + 1);
			$tablename = substr($fieldname, 0, $dotpos);
			return [ $tablename, $fldname ];
		} else {
			return [ "", $fieldname ];
		}
	}


}
