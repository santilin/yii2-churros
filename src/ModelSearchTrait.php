<?php
namespace santilin\churros;

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
	static public $operators = [
		'=' => '=',
		'<>' => '<>',
		'START' => 'Comienza por', 'NOT START' => 'No comienza por',
		'LIKE' => 'Contiene', 'NOT LIKE' => 'No contiene',
		'<=' => '<=',
		'>=' => '>=',
		'>' => '>',
		'<' => '<',
		'SELECT' => 'Valor(es) de la lista',
		'BETWEEN' => 'entre dos valores',
		'NOT BETWEEN' => 'no entre dos valores',
	];

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
			parent::__set($name, $value);
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
    public function addColumnSortsToProvider($gridColumns, &$provider)
    {
		foreach( $gridColumns as $attribute => $column_def ) {
			if ( is_int($attribute)
				|| $attribute == '__actions__'
				|| array_key_exists($attribute, $this->attributes ) ) {
				continue;
			}
			$attribute = $column_def['attribute'];
			if( strpos($attribute, '.') === FALSE ) {
				$relation_name = $attribute;
				$sort_fldname = '';
			} else {
				list($relation_name, $sort_fldname) = ModelInfoTrait::splitFieldName($attribute);
			}
			if (isset(self::$relations[$relation_name]) ) {
				$related_model_class = self::$relations[$relation_name]['modelClass'];
				$table_alias = "as_$relation_name";
				// Activequery removes duplicate joins
				$provider->query->joinWith("$relation_name $table_alias");
				$provider->query->distinct(); // Evitar duplicidades debido a las relaciones hasmany
				if ($sort_fldname == '' ) { /// @todo junction tables
					$code_field = $related_model_class::instance()->findCodeField();
					$sort_fldname = $code_field;
				}
				if( $sort_fldname != '' && !isset($provider->sort->attributes[$attribute])) {
					$related_model_search_class = $related_model_class::getSearchClass();
					if( class_exists($related_model_search_class) ) {
						// Set orders from the related search model
						$related_model = new $related_model_search_class;
						$related_model_provider = $related_model->search([]);
						if (isset( $related_model_provider->sort->attributes[$sort_fldname]) ) {
							$related_sort = $related_model_provider->sort->attributes[$sort_fldname];
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

    public function load($data, $formName = null)
    {
        $scope = $formName === null ? $this->formName() : $formName;
        if (isset($data[$scope])) {
			foreach( $data[$scope] as $name => &$value ) {
				if( is_array($value) ) {
					$value = json_encode($value);
				}
				$this->$name = $value;
			}
            return true;
        }
        return false;
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

	static public function toOpExpression($value, $strict)
	{
		if( isset($value['op']) ) {
			return $value;
		}
		if( is_string($value) && $value != '') {
			if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
				return json_decode($value, true);
			} else if( preg_match('/^(=|<>|<=|>=|>|<)(.*)$/', $value, $matches) ) {
				return [ 'v' => $matches[2], 'op' => $matches[1] ];
			}
		}
		return [ 'op' => $strict ? '=' : 'LIKE', 'v' => $value ];
	}

	public function filterWhere(&$query, $fldname, $value)
	{
		$value = static::toOpExpression($value, false );
		if( $value['v'] == null ) {
			return;
		}
		if( true !== $this->customFilterWhere($query, $fldname, $value) ) {
			if( is_array($value['v']) ) {
				$query->andWhere([ 'in', $fldname, $value['v']]);
			} else switch( $value['op'] ) {
				case "=":
					$query->andWhere([$fldname => $value['v']]);
					break;
				case "<>":
				case ">=":
				case "<=":
				case ">":
				case "<":
				case "NOT LIKE":
				case "LIKE":
					$query->andWhere([ $value['op'], $fldname, $value['v'] ]);
					break;
				case "START":
					$query->andWhere([ 'LIKE', $fldname, $value['v'] . '%', false]);
					break;
				case "NOT START":
					$query->andWhere([ 'NOT LIKE', $fldname, $value['v'] . '%', false]);
					break;
				case "BETWEEN":
				case "NOT BETWEEN":
					$query->andWhere([ $value['op'], $fldname, explode(',',$value['v']) ]);
					break;
			}
		}
	}

	public function customFilterWhere(&$query, string $fldname, array $value)
	{
		return false;
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
			list($relation_name, $attribute) = ModelInfoTrait::splitFieldName($name);
		}
		$relation = self::$relations[$relation_name]??null;
		if( $relation ) {
			// Hay tres tipos de campos relacionados:
			// 1. El nombre de la relación (attribute = '' )
			// 2. Relación y campo: Productora.nombre
			// 3. La clave foranea: productura_id
			$table_alias = "as_$relation_name";
			// Activequery removes duplicate joins (added also in addSort)
			$query->joinWith("$relation_name $table_alias");
			$value = static::toOpExpression($value, false );
			$modelClass = $relation['modelClass'];
			$model = $modelClass::instance();
			$search_flds = [];
			if ($attribute == $model->primaryKey()[0] ) {
				if( isset($relation['other']) ) {
					list($right_table, $right_fld ) = ModelInfoTrait::splitFieldName($relation['other']);
				} else {
					list($right_table, $right_fld ) = ModelInfoTrait::splitFieldName($relation['right']);
				}
				$query->andWhere([$value['op'], "$table_alias.$right_fld", $value['v'] ]);
			} else if( $attribute == '' ) {
				$search_flds = $model->findCodeAndDescFields();
				$rel_conds = [ 'OR' ];
				foreach( $search_flds as $search_fld ) {
					$rel_conds[] = [$value['op'], "$table_alias.$search_fld", $value['v'] ];
				}
				$query->andWhere( $rel_conds );
			} else {
				$query->andWhere([$value['op'], "$table_alias.$attribute", $value['v'] ]);
			}
		} else {
			throw new InvalidArgumentException($relation_name . ": relation not found in model " . self::class . ' (SearchModel::filterWhereRelated)');
		}
	}


	protected function filterGlobal(&$query, array $attributes, string $value)
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
					$query->orWhere( [ 'LIKE', $fullfldname, $value ] );
					continue;
				} else {
					$relation_name = $name;
					$attribute = '';
				}
			} else {
				list($relation_name, $attribute) = ModelInfoTrait::splitFieldName($name);
				$relation = self::$relations[$relation_name]??null;
			}
			if( $relation ) {
				// Hay tres tipos de campos relacionados:
				// 1. El nombre de la relación (attribute = '' )
				// 2. Relación y campo: Productora.nombre
				// 3. La clave foranea: productura_id
				$table_alias = "as_$relation_name";
				// Activequery removes duplicate joins (added also in addSort)
				$query->joinWith("$relation_name $table_alias");
				$modelClass = $relation['modelClass'];
				$model = $modelClass::instance();
				$search_flds = [];
				if ($attribute == $model->primaryKey()[0] ) {
					if( isset($relation['other']) ) {
						list($right_table, $right_fld ) = ModelInfoTrait::splitFieldName($relation['other']);
					} else {
						list($right_table, $right_fld ) = ModelInfoTrait::splitFieldName($relation['right']);
					}
					$query->orWhere([ 'IN', "$table_alias.$right_fld", $value ]);
				} else if( $attribute == '' ) {
					$search_flds = $model->findCodeAndDescFields();
					$rel_conds = [ 'OR' ];
					foreach( $search_flds as $search_fld ) {
						$rel_conds[] = [ 'LIKE', "$table_alias.$search_fld", $value ];
					}
					$or_conds[] = $rel_conds;
				} else {
					$or_conds[] = ['LIKE', "$table_alias.$attribute", $value ];
				}
			} else {
				throw new InvalidArgumentException($relation_name . ": relation not found in model " . self::class . ' (SearchModel::filterWhereRelated)');
			}
		}
		if( count( $or_conds ) > 1 ) {
			$query->andWhere($or_conds);
		}
	}


} // class
