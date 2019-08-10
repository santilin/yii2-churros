<?php namespace santilin\churros;

use Yii;

/**
 * Eases the definition of filters and sorts in grids for search models
 *
 * Creates the properties that hold the related filter values.
 * Extracts the sort and where properties from the related search model data provider
 * @todo Extract the rules from the related model
 */
trait ModelRelatedSearchTrait
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

}
