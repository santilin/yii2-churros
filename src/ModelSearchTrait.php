<?php namespace santilin\churros;

use Yii;
use yii\helpers\Html;

/**
 * Eases the definition of filters and sorts in grids for search models
 *
 * Creates the properties that hold the related filter values.
 * Extracts the sort and where properties from the related search model data provider
 * @todo Extract the rules from the related model
 */
trait ModelSearchTrait
{
	private $related_properties = [];
	private $dynamic_rules = [];

    public function setAttributes($values, $safeOnly = true)
    {
		foreach( $values as $attribute => $value ) {
			if (!array_key_exists($attribute, $this->attributes) ) {
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
			return parent::__get($name);
		}
	}

//     public function __set($name, $value)
//     {
// 		try {
// 			return parent::__set($name, $value);
// 		} catch (\yii\base\UnknownPropertyException $e) {
// 			$this->related_properties[$name] = $value;
// 		}
// 	}

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
	 * Adds related sorts and filters to dataproviders
	*/
    public function addColumnsSortsFiltersToProvider($gridColumns, &$provider)
    {
		foreach( $gridColumns as $attribute => $colum_def ) {
			if ( is_int($attribute) || array_key_exists($attribute, $this->attributes ) ) {
				continue;
			}
			$fldname = '';
			$relation_name = $attribute;
			if( ($dotpos = strpos($attribute, '.')) !== FALSE ) {
				$fldname = substr($attribute, $dotpos + 1);
				$relation_name = substr($attribute, 0, $dotpos);
			}
			$relation = $this->getRelation($relation_name, false);
			$filter_set = false;
			if( $relation != null ) {
				$table_alias = "as_$relation_name"; /// @todo check that the join is added only once
				$provider->query->joinWith("$relation_name $table_alias");
				if ($fldname == '' ) { /// @todo junction tables
					$related_model_class = $relation->modelClass;
					list($code_field, $desc_field) = $related_model_class::getCodeDescFields();
					if( $desc_field != '' && $code_field != '' ) {
						$provider->query->andFilterWhere(['or',
							[ 'LIKE', "$table_alias.$desc_field", $this->{$attribute} ],
							[ 'LIKE', "$table_alias.$code_field", $this->{$attribute} ]
						]);
						$filter_set = true;
						$fldname = $code_field;
					}
				}
				if (!$filter_set) {
					$provider->query->andFilterWhere(
						['LIKE', "$table_alias.$fldname", $this->{$attribute}]);
				}
				if (!isset($provider->sort->attributes[$attribute])) {
					// Set orders from the related search model
					$related_model_search_class = $related_model_class . "Search";
					if( class_exists($related_model_search_class) ) {
						$related_model = new $related_model_search_class;
						$related_model_provider = $related_model->search(
							[ $related_model->formName() =>
								[ $fldname => $this->{$attribute}]
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
		if( $value == null ) {
			return;
		}
		if( is_array($value) ) {
			assert(false);
		}
		if( !in_array( $name, array_keys(self::$relations) ) ) {
			$name = $this->tableName() . '.' . $name;
		}
		if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
			$condition = json_decode($value);
			if( $condition->lft == null ) {
				return;
			}
			switch( $condition->op ) {
				case "=":
					return [ $name => $condition->lft ];
				case "<>":
				case ">=":
				case "<=":
				case ">":
				case "<":
				case "NOT LIKE":
				case "LIKE":
					$query->andFilterWhere([ $condition->op, $name, 
						$condition->lft ]);
						break;
				case "BETWEEN":
				case "NOT BETWEEN":
					$query->andFilterWhere([ $condition->op, $name, 
						$condition->lft, $condition->rgt ]);
					break;
			}
		} else if( !empty($value) ) {
			if( is_numeric($value) ) {
				$query->andWhere([ $name => $value ]);
			} else {
				$query->andWhere([ 'like', $name, $value ]);
			}
		}
	}
	

	public function load($params, $formName = null)
	{
		if( !isset($params['_pjax']) ) {
			// join search form params
			$scope = $formName === null ? $this->formName() : $formName;
			$newparams = [];
			parent::load($params, $formName);
			if( isset($params[$scope]['_search_']) ) {
				foreach( $params[$scope]['_search_'] as $name => $values) {
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
	 * Returns Html code to add an advanced search field to a form
	 */
	public static function searchField($model, $attribute)
	{
		$ret = '';
		static $operators = [ 
			'=' => '=', '<>' => '<>', 
			'LIKE' => 'Contiene', 'NOT LIKE' => 'No contiene',
			'>' => '>', '<' => '<', 
			'>=' => '>=', '<=' => '<=', 
			'BETWEEN' => 'entre', 'NOT BETWEEN' => 'no entre' ];
		$scope = $model->formName();
		$value = $model->$attribute;
		if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
			$value = json_decode($value);
			if( !isset($value->lft) ) {
				$value = (object)[ 'op' => '', 'lft' => '', 'rgt' => ''];
			}
		} else {
			$value = (object)['op' => '', 'lft' => $value, 'rgt' => ''];
		}
		$ret .= "<div class='row'>";
		$ret .= "<div class='form-group'>";
		$ret .= "<div class='control-label col-sm-3'>";
		$ret .= Html::activeLabel($model, $attribute);
		$ret .= "</div>";
			
		$ret .= "<div class='control-form col-sm-2'>";
		$ret .= Html::dropDownList("${scope}[_search_][$attribute][op]",
			$value->op, $operators, [ 
			'id' => "drop-$attribute", 'class' => 'search-dropdown form-control col-sm-2'] );
		$ret .= "</div>";
			
		$ret .= "<div class='control-form col-sm-4'>";
		$ret .= Html::input('text', "${scope}[_search_][$attribute][lft]", 
			$value->lft, [ 'class' => 'form-control col-sm-4']);
		$ret .= "</div>";
		$ret .= "</div><!-- row -->";
		
		
		$ret .= "<div class='row gap10'>";
		$ret .= "<div id='second-field-drop-$attribute' style='display:none'>";
		$ret .= "<div class='control-form col-sm-2 col-sm-offset-3'>";
		$ret .= "y:";
		$ret .= "</div>";
		$ret .= "<div class='control-form col-sm-4'>";
		$ret .= Html::input('text', "${scope}[_search_][$attribute][rgt]", 
			$value->rgt, [ 'class' => 'form-control col-sm-4']);
		$ret .= "</div>";
		$ret .= "</div><!-- row -->";
		$ret .= "</div>";
		$ret .= Html::activeHiddenInput($model, $attribute);
		$ret .= "</div>";
		return $ret;
	}
	
	static public $searchFormJS = <<<JS
$('.search-dropdown').change(function() {
	let value= $(this).val();
	console.log($('#second-field-' + this.id).html());
	if( value == 'BETWEEN' || value == 'NOT BETWEEN' ) {
		$('#second-field-' + this.id).show(200);
	} else {
		$('#second-field-' + this.id).hide(200);
	}
});
JS;

	
}
