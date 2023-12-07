<?php
namespace santilin\churros;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\base\InvalidArgumentException;
use santilin\churros\Helpers\AppHelper;

/**
 * Eases the definition of filters and sorts in grids for search models
 *
 * Creates the properties that hold the related filter values.
 * Extracts the sort and where properties from the related search model data provider
 * @todo Extract the rules from the related model
 */
trait ModelSearchTrait
{
	public $_gridPageSize = 20;

	public function __get($name)
	{
		// GridView::renderFilter: needs activeAttribute when related property
		if( property_exists($this, 'related_properties') ) {
			if( array_key_exists($name, $this->related_properties) ) {
				return $this->related_properties[$name];
			}
		}
		return parent::__get($name);
	}

	public function __set($name, $value)
	{
		if ($this->hasAttribute($name) || property_exists($this,$name) ) {
			$this->$name = $value;
		} else if( array_key_exists($name, $this->related_properties) ) {
			$this->related_properties[$name] = $value;
		}
	}

	public function addScopes( $scopes, &$provider )
	{
		foreach( $scopes as $scope ) {
			$provider->query->$scope();
		}
	}

	/**
	 * Adds related sorts and filters to dataproviders for grids
	*/
    public function addRelatedSortsToProvider($gridColumns, &$provider)
    {
		foreach ($gridColumns as $attribute => $column_def) {
			if (is_int($attribute)
				|| $attribute == '__actions__'
				|| array_key_exists($attribute, $this->attributes ) ) {
				continue;
			}
			$attribute = $column_def['attribute'];
			if (strpos($attribute, '.') === FALSE) {
				$relation_name = $attribute;
				$sort_fldname = '';
			} else {
				list($relation_name, $sort_fldname) = AppHelper::splitFieldName($attribute);
			}
			if (isset(self::$relations[$relation_name]) ) {
				$related_model_class = self::$relations[$relation_name]['modelClass'];
				$table_alias = "as_$relation_name";
				// Activequery removes duplicate joins
				$provider->query->joinWith("$relation_name $table_alias");
				$provider->query->distinct(); // Evitar duplicidades debido a las relaciones hasmany
				if (!isset($provider->sort->attributes[$attribute])) {
					$related_model_search_class = self::$relations[$relation_name]['searchClass']
						?? str_replace('models\\', 'forms\\', $related_model_class) . '_Search';
					if( class_exists($related_model_search_class) ) {
						if ($sort_fldname == '' ) { /// @todo junction tables
							$code_field = $related_model_class::instance()->findCodeField();
							$sort_fldname = $code_field;
						}
						// Set orders from the related search model
						$related_model = new $related_model_search_class;
						$related_model_provider = $related_model->search([]);
						if (isset( $related_model_provider->sort->attributes[$sort_fldname]) ) {
							$related_sort = $related_model_provider->sort->attributes[$sort_fldname];
							if (isset($related_sort['label'])) {
								$new_related_sort = [ 'label' => $related_sort['label']];
								unset($related_sort['label']);
							} else {
								$new_related_sort = [];
							}
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

	/*
	 * Intenta poner un valor legible en el filtro del gridview
	 */
	public function transformGridFilterValues()
	{
		foreach( array_merge($this->normal_attrs, array_keys($this->related_properties)) as $attr ) {
			$value = $this->$attr;
			if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
				$value = json_decode($value, true);
			}
			if( isset($value['v']) ) {
				if( in_array($value['op'], ['=','<','>','<=','>=','<>'] ) ) {
					$this->$attr = $value['op'] . $value['v'];
				} else if( $value['op'] == 'LIKE' ) {
					$this->$attr = $value['v'];
				}
			}
		}
	}

	protected function filterWhereRelated(&$query, $relation_name, $value)
	{
		if ($value === null || $value === '') {
			return;
		}
		$model = $this;
		$nested_relations = '';
		if (strpos($relation_name, '.') === FALSE) {
			$relation = $model::$relations[$relation_name]??null;
			if( $relation ) {
				// Hay tres tipos de campos relacionados:
				// 1. El nombre de la relación (attribute = '' )
				// 2. Relación y campo: Productora.nombre
				// 3. La clave foranea: productura_id
				$table_alias = "as_$relation_name";
				// Activequery removes duplicate joins (added also in addSort)
				$query->joinWith("$relation_name $table_alias");
				$attribute = '';
				$modelClass = $relation['modelClass'];
				$model = $modelClass::instance();
			}
		} else {
			while (strpos($relation_name, '.') !== FALSE) {
				list($relation_name, $attribute) = AppHelper::splitFieldName($relation_name);
				$relation = $model::$relations[$relation_name]??null;
				if ($nested_relations!='') {
					$nested_relations .= '.';
				}
				$nested_relations .= $relation_name;
				if( $relation ) {
					// Hay tres tipos de campos relacionados:
					// 1. El nombre de la relación (attribute = '' )
					// 2. Relación y campo: Productora.nombre
					// 3. La clave foranea: productura_id
					$table_alias = "as_$relation_name";
					// Activequery removes duplicate joins (added also in addSort)
					$query->joinWith("$nested_relations $table_alias");
					$modelClass = $relation['modelClass'];
					$model = $modelClass::instance();
				} else {
					throw new InvalidArgumentException($relation_name . ": relation not found in model " . self::class . ' (SearchModel::filterWhereRelated)');
				}
			}
			if( $relation ) {
				// Hay tres tipos de campos relacionados:
				// 1. El nombre de la relación (attribute = '' )
				// 2. Relación y campo: Productora.nombre
				// 3. La clave foranea: productura_id
				$table_alias = "as_$attribute";
				// Activequery removes duplicate joins (added also in addSort)
				$query->joinWith("$nested_relations $table_alias");
			}
		}

		$value = static::toOpExpression($value, false );
		$search_flds = [];
		if ($attribute == '') {
			$search_flds = $model->findCodeAndDescFields();
			$rel_conds = [ 'OR' ];
			foreach( $search_flds as $search_fld ) {
				$rel_conds[] = [$value['op'], "$table_alias.$search_fld", $value['v'] ];
			}
			$query->andWhere( $rel_conds );
		} else if ($attribute == $model->primaryKey()[0] ) {
			if( isset($relation['other']) ) {
				list($right_table, $right_fld ) = AppHelper::splitFieldName($relation['other']);
			} else {
				list($right_table, $right_fld ) = AppHelper::splitFieldName($relation['right']);
			}
			if ($value['v']=== 'true') {
				$query->andWhere(["not", ["$table_alias.$right_fld" => null]]);
			} else if ($value['v'] === 'false') {
				$query->andWhere(["$table_alias.$right_fld" => null]);
			} else {
				$query->andWhere(["$table_alias.$right_fld" => $value['v']]);
			}
		} else {
			$query->andWhere([$value['op'], "$table_alias.$attribute", $value['v'] ]);
		}
	}


	protected function filterGlobal(&$query, array $attributes, string $value, $inclusiva = false)
	{
		if( $value === null || $value === '' ) {
			return;
		}
		$or_conds = [ 'OR' ];
		foreach( $attributes as $name ) {
			if( $name == 'globalSearch' ) {
				continue;
			}
			if( strpos($name, '.') === FALSE) {
				$relation = self::$relations[$name]??null;
				if( !$relation ) {
					$fullfldname = $this->tableName() . "." . $name;
					$or_conds[] = [ 'LIKE', $fullfldname, $value ];
					continue;
				} else {
					$relation_name = $name;
					$attribute = '';
				}
			} else {
				list($relation_name, $attribute) = AppHelper::splitFieldName($name);
				$relation = self::$relations[$relation_name]??null;
			}
			if( $relation ) {
				// Hay tres tipos de campos relacionados:
				// 1. El nombre de la relación (productora, attribute = '' )
				// 2. Relación y campo: Productora.nombre
				// 3. La clave foranea: productura_id
				$table_alias = "as_$relation_name";
				// Activequery removes duplicate joins (added also in addSort)
				$query->joinWith("$relation_name $table_alias");
				$modelClass = $relation['modelClass'];
				$model = $modelClass::instance();
				$search_flds = [];
				if( $attribute == '' ) {
					$search_flds = $model->findCodeAndDescFields();
					$rel_conds = [ 'OR' ];
					foreach( $search_flds as $search_fld ) {
						$rel_conds[] = [ 'LIKE', "$table_alias.$search_fld", $value ];
					}
					$or_conds[] = $rel_conds;
				} elseif ($attribute == $model->primaryKey()[0] ) {
					if( isset($relation['other']) ) {
						list($right_table, $right_fld ) = AppHelper::splitFieldName($relation['other']);
					} else {
						list($right_table, $right_fld ) = AppHelper::splitFieldName($relation['right']);
					}
					$or_conds[] = [ 'IN', "$table_alias.$right_fld", $value ];
				} else {
					$or_conds[] = ['LIKE', "$table_alias.$attribute", $value ];
				}
			} else {
				throw new InvalidArgumentException($relation_name . ": relation not found in model " . self::class . ' (SearchModel::filterWhereRelated)');
			}
		}
		if( count( $or_conds ) > 1 ) {
			if( $inclusiva ) {
				$query->orWhere($or_conds);
			} else {
				$query->andWhere($or_conds);
			}
		}
	}

} // class
