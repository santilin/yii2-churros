<?php namespace santilin\churros;

use Yii;
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
	static public $operators = [
			'=' => '=',
			'===' => 'Exactamente', // Distinguish = (in grid filter) from === in search form
			'<>' => '<>',
			'LIKE' => 'Contiene', 'NOT LIKE' => 'No contiene',
			'>' => '>', '<' => '<',
			'>=' => '>=', '<=' => '<=',
			'BETWEEN' => 'entre', 'NOT BETWEEN' => 'no entre' ];
	static public $extra_operators = [
			'BETWEEN', 'NOT BETWEEN' ];

	/*
	 * Called when setting a filter in search()
	 */
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
    public function addColumnSortsToProvider($gridColumns, &$provider)
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
				$table_alias = "as_$relation_name";
				// Activequery removes duplicate joins
				$provider->query->joinWith("$relation_name $table_alias");
				if ($fldname == '' ) { /// @todo junction tables
					list($code_field, $desc_field) = $related_model_class::getCodeDescFields();
					if( $desc_field != '' && $code_field != '' ) {
						$fldname = $code_field;
					}
				}
				if (!isset($provider->sort->attributes[$attribute])) {
					$related_model_search_class = $related_model_class::getSearchClass();
					if( class_exists($related_model_search_class) ) {
						// Set orders from the related search model
						$related_model = new $related_model_search_class;
						$related_model_provider = $related_model->search([]);
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
		if( is_array($values) ) {
			return json_encode($values);
		} else {
			return $values;
		}
	}

	public function transformGridFilterValues()
	{
		foreach( $this->attributes as $name => $value ) {
			if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
				$value = json_decode($value, true);
			}
			if( isset($value['lft']) ) {
				if( in_array($value['op'], ['=','<','>','<=','>=','<>'] ) ) {
					$this->$name = $value['op'] . $value['lft'];
				} else if( $value['op'] == 'LIKE' ) {
					$this->$name = $value['lft'];
				}
			}
		}
	}

	protected function toOpExpression($value, $strict)
	{
		if( isset($value['op']) ) {
			return $value;
		} else if( is_string($value) && $value != '') {
			if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
				return json_decode($value, true);
			} else if( preg_match('/^(=|<>|<|<=|>|>=)(.*)$/', $value, $matches) ) {
				return [ 'lft' => $matches[2], 'op' => $matches[1], 'rgt' => null ];
			}
		}
		return [ 'op' => $strict ? '=' : 'LIKE', 'lft' => $value, 'rgt' => '' ];
	}


	public function filterWhere(&$query, $name, $value)
	{
		$value = $this->toOpExpression($value, false );
		if( $value['lft'] == null ) {
			return;
		}
		// addColumnSortsToProvider adds the join tables with AS `as_xxxxxxx`
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
		if( is_array($value['lft']) ) {
 			$query->andWhere([ 'in', $name, $value['lft']]);
		} else switch( $value['op'] ) {
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
// 		} else if( is_numeric($value) && !is_string($value) ) {
// 			$query->andWhere([ $name => $value ]);
// 		} else {
// 			if( $value[0] == '=' ) {
// 				$query->andWhere([ 'OR', [ 'like', $name, $value ], [ $name => substr($value,1) ]]);
// 			} else {
// 				$query->andWhere([ 'like', $name, $value ]);
// 			}
// 		}
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
			$table_alias = "as_$relation_name";
			// Activequery removes duplicate joins (added also in addSort)
			$query->joinWith("$relation_name $table_alias");
			$value = $this->toOpExpression($value, false );
			if ($attribute == '' ) {
				list($code_field, $desc_field) = $related_model_class::getCodeDescFields();
				if( $desc_field != '' || $code_field != '' ) {
					if( $code_field == '' ) {
						$code_field = $desc_field;
					}
					$query->andFilterWhere([ $value['op'], "$table_alias.$code_field", $value['lft']
					]);
					$filter_set = true;
				} else {
					throw new \Exception("table $related_model_class doesn't have a code field");
				}
			}
			if (!$filter_set) {
				$query->andFilterWhere([$value['op'], "$table_alias.$attribute", $value['lft'] ]);
			}
		} else {
			throw new InvalidArgumentException($relation_name . ": relation not found in model " . self::class);
		}
	}

	/**
	 * Loads the advanced search form data
	 */
	public function load($params, $formName = null)
	{
		if( isset($params['_pjax']) ) {
			// filter form inside grid
			return parent::load($params, $formName);
		} else {
			// filter form outside grid
			// join search form params
			$scope = ($formName === null) ? $this->formName() : $formName;
			$ret = parent::load($params, $formName);
			if( $ret ) {
				$newparams = [];
				if( isset($params[$scope]['_adv_']) ) {
					foreach( $params[$scope]['_adv_'] as $name => $values) {
						if( isset($values['lft'])  && $values['lft']!=='' && $values['lft']!==null ) {
							$newparams[$name] = $this->makeSearchParam($values);
						}
					}
					return parent::load([ $scope => $newparams], $formName);
				}
			}
			return true;
		}
	}

	/**
	 * Returns Html code to add an advanced search field to a search form
	 * Almost identical to ReportsModelTrait::createSearchField
	 * @todo dropdowns
	 */
	/**
	 * Returns Html code to add an advanced search field to a search form
	 * @param boolean $hidden Whether to include the general condition as a hidden input
	 */
	public function createSearchField($model, $attribute, $type = 'string', $options = [],
		$dropdown_values = null )
	{
		$attr_class = str_replace('.','_',$attribute);
		if( ($control_type = $type) == 'date' ) {
			$control_type = 'string';
		}
		if ( (isset($options['hideme']) && $options['hideme'] == true)
			|| (isset($options['visible']) && $options['visible'] == false) ) {
			$main_div = ' class="row collapse hideme"';
		} else {
			$main_div = '';
		}
		unset($options['hideme']);
		$ret = '';
		$scope = $model->formName();
		if( isset( $model->$attribute) ) {
			$value = $model->$attribute;
		} else if( isset( $this->report_filters[$attribute] ) ) {
			$value = $this->report_filters[$attribute];
		} else {
			$value = null;
		}
		$value = $this->toOpExpression($value, false);
		if( !in_array($value['op'], ModelSearchTrait::$extra_operators) ) {
			$extra_visible = "display:none";
		} else {
			$extra_visible = '';
		}
		$ret .= "<div$main_div>";
		$ret .= "<div class='form-group'>";
		$ret .= "<div class='control-label col-sm-3'>";
		$ret .= Html::activeLabel($model, $attribute, $options);
		if ($type == 'date' ) {
			$ret .= "<br>Formato yyyy-mm-dd";
		}
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
			$ret .= Html::input($control_type, "${scope}[_adv_][$attribute][lft]",
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
			$ret .= Html::input($control_type, "${scope}[_adv_][$attribute][rgt]",
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

	static public function splitFieldName($fieldname)
	{
		if( ($dotpos = strrpos($fieldname, '.')) !== FALSE ) {
			$fldname = substr($fieldname, $dotpos + 1);
			$tablename = substr($fieldname, 0, $dotpos);
			return [ $tablename, $fldname ];
		} else {
			return [ "", $fieldname ];
		}
	}

	public function addRelatedField($attribute, &$joins)
	{
		$left_model = $this;
		$tablename = $alias = '';
		while( ($dotpos = strpos($attribute, '.')) !== FALSE ) {
			$relation_name = substr($attribute, 0, $dotpos);
			if( !empty($alias) ) { $alias .= "_"; }
			$alias .= $relation_name;
			$attribute = substr($attribute, $dotpos + 1);
			if( $relation_name == str_replace(['{','}','%'],'',$this->tableName() ) ) {
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
				$left_model = new $relation['modelClass'];
			} else {
				throw new \Exception($relation_name . ": relation not found in model " . $left_model->className() . " with relations " . join(',', array_keys($left_model::$relations)));
			}
		}
		$alias .= "_$attribute";
		return [ $tablename, $attribute, $alias ];
	}


}
