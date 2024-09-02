<?php
namespace santilin\churros;

use Yii;
use yii\base\InvalidArgumentException;
use yii\helpers\{Html,ArrayHelper,StringHelper};
use yii\data\BaseDataProvider;
use santilin\churros\helpers\{AppHelper,FormHelper};

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
		if (property_exists($this,$name)) {
			$this->$name = $value;
		} else if ($this->hasAttribute($name)) {
			$this->setAttribute($name, $value);
		} else if (array_key_exists($name, $this->related_properties)) {
			$this->related_properties[$name] = $value;
		}
	}

    public function operatorForAttr(string $attr): string
	{
		if (array_key_exists($attr, $this->related_properties)) {
			return '=';
		} else {
			if (array_key_exists($attr, $this->normal_attrs)) {
				return $this->normal_attrs[$attr];
			} else if ($attr == 'id') {
				return '=';
			} else {
				return 'LIKE';
			}
		}
	}

	public function addScope(&$provider, string $scope, array $scopes_arguments)
	{
		$provider->query->$scope(...$scopes_arguments);
	}

	public function addScopes(&$provider, array $scopes)
	{
		foreach ($scopes as $scope) {
			$provider->query->$scope();
		}
	}

	/**
	 * Adds related filters and sorts to dataproviders for grids
	*/
    public function addRelatedFieldsToProvider(array $gridColumns, BaseDataProvider $provider)
    {
		foreach ($gridColumns as $col_attribute => $column_def) {
			if ( $column_def === null // Allows for conditional definition of columns
// 				|| is_int($attribute)
				|| StringHelper::endsWith($column_def['class']??'','ActionColumn') ) {
				continue;
			}
			if (!empty($column_def['filterAttribute'])) {
				$this->addRelatedFieldToJoin($column_def['filterAttribute'], $provider->query);
			}
			if (array_key_exists($col_attribute, $this->attributes)) { // for sorting
				continue;
			}
			$attribute = $column_def['attribute']??null;
			if ($attribute === null) {
				continue;
			}
			if ($col_attribute != $attribute) {
				throw new \Exception("$col_attribute != " . ($column_def['attribute']??null));
			}
			list($sort_fldname, $table_alias, $model, $relation) = $this->addRelatedFieldToJoin($attribute, $provider->query);
			// continue;
			if ($sort_fldname != $attribute) {
				/// @todo recursive tarea.paqueteTrabajo.proyecto.id
				$related_model_class = $relation['modelClass'];
// 				$provider->query->distinct(); // Evitar duplicidades debido a las relaciones hasmany
				if (!isset($provider->sort->attributes[$attribute])) {
					$related_model_search_class = $relation['searchClass']
						?? str_replace('models\\', 'forms\\', $related_model_class) . '_Search';
					if (class_exists($related_model_search_class)) {
						// Set orders from the related search model
						$related_model = $related_model_search_class::instance();
						$related_model_provider = $related_model->search([]);
						if (isset($related_model_provider->sort->attributes[$sort_fldname]) ) {
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
							$provider->sort->attributes[$attribute] = $new_related_sort;
						} else {
							$provider->sort->attributes[$attribute] = $related_model_provider->sort->defaultOrder;
						}
					} else {
						$provider->sort->attributes[$attribute] = [
							'asc' => [$table_alias.'.'.$sort_fldname => SORT_ASC ],
							'desc' => [$table_alias.'.'.$sort_fldname => SORT_DESC ]
						];
					}
				}
			}
		}
    }

	/**
	 * Adds a related field to the joins of a query
	 * @return the name of the attribute to be used in the query
	 */
	protected function addRelatedFieldToJoin(string $field_name, $query): array
	{
		$model = $this;
		$attribute = $field_name;
		$nested_relations = [];
		$model = $this;
		$table_alias = 'as';
		$relation = null;
		while (strpos($attribute, '.') !== FALSE) {
			list($field_name, $attribute) = AppHelper::splitString($attribute, '.');
			$relation = $model::$relations[$field_name]??null;
			if ($relation) {
				if ($relation['type'] == 'ManyToMany') {
					$junction_relation = $model::$relations[$relation['join']];
					$junction_model_class = $junction_relation['modelClass'];
					$junction_model = $junction_model_class::instance();
					$junction_table_alias = $table_alias . '_join_' . $field_name;
					$nested_relations[$junction_table_alias] = $junction_relation['relatedTablename'];
					/// @todo $final_attribute when nested
					$this->addJoinIfNotExists($query, $nested_relations, "LEFT JOIN", [ $junction_table_alias => $junction_model->tableName()], $junction_relation['join']);
					$related_table_alias = $junction_table_alias . '_' . $field_name;
					$nested_relations[$related_table_alias] = $relation['relatedTablename'];
					$model_class = $relation['modelClass'];
					$model = $model_class::instance();
					foreach ($junction_model::$relations as $nr => $related_relation) {
						if ($related_relation['modelClass'] == $model_class) {
							break;
						}
					}
					if ($this->addJoinIfNotExists($query, $nested_relations, "LEFT JOIN", [ $related_table_alias => $model->tableName()], $related_relation['join'])) {
						$final_attribute = $related_table_alias . '.' . $attribute;
					} else {
						$final_attribute = $relation['other'];
						$table_alias = $junction_model->tableName();
					}
				} else {
					$table_alias .= "_$field_name";
					$model_class = $relation['modelClass'];
					$model = $model_class::instance();
					$nested_relations[$table_alias] = $relation['relatedTablename'];
					$this->addJoinIfNotExists($query, $nested_relations, "LEFT JOIN", [ $table_alias => $model->tableName()], $relation['join']);
					$final_attribute = $attribute;
				}
			} else {
				throw new InvalidArgumentException($field_name . ": relation not found in model " . self::class . ' (SearchModel::filterWhereRelated)');
			}
		}
		if (isset($model::$relations[$attribute])) {
			$relation = $model::$relations[$attribute];
			$model_class = $relation['modelClass'];
			$model = $model_class::instance();
			// Hay tres tipos de campos relacionados:
			// 1. El nombre de la relación (attribute = '' )
			// 2. Relación y campo: Productora.nombre
			// 3. La clave foranea: productura_id
			$table_alias .= "_$attribute";
			$nested_relations[$table_alias] = $relation['relatedTablename'];
			$this->addJoinIfNotExists($query, $nested_relations, "LEFT JOIN", [ $table_alias => $model->tableName()], $relation['join']);
			$final_attribute = $model->primaryKey()[0];
		}
		return [$final_attribute??$attribute,$table_alias,$model,$relation];
	}


	protected function filterWhereRelated($query, $relation_name, $value, $is_and = true)
	{
		if ($value === null || $value === '' ||
			(is_array($value) && (!isset($value['v']) || (isset($value['v']) && $value['v']==='')))) {
			return;
		}
		list($attribute, $table_alias, $model, $relation) = $this->addRelatedFieldToJoin($relation_name, $query);

		$value = FormHelper::toOpExpression($value, false, $this->operatorForAttr($attribute?:$relation_name) );
		$search_flds = [];
		if ($attribute == '') {
			$search_flds = $model->findCodeAndDescFields();
			$rel_conds = [ 'OR' ];
			foreach( $search_flds as $search_fld ) {
				$fld_conds = [ 'OR' ];
				$operator = $this->operatorForAttr($search_fld);
				foreach ((array)$value['v'] as $v) {
					$fld_conds[] = [ $operator, "$table_alias.$search_fld", $v];
				}
				$rel_conds[] = $fld_conds;
			}
			if ($is_and) {
				$query->andWhere($rel_conds);
			} else {
				$query->orWhere($rel_conds);
			}
		} else if ($attribute == $model->primaryKey()[0] ) {
			if (isset($relation['other']) ) {
				list($right_table, $right_fld ) = AppHelper::splitFieldName($relation['other']);
			} else {
				list($right_table, $right_fld ) = AppHelper::splitFieldName($relation['right']);
			}
			if ($value['v']=== 'true') {
				$query->andWhere(["not", ["$table_alias.$right_fld" => null]]);
			} else if ($value['v'] === 'false') {
				if ($is_and) {
					$query->andWhere(["$table_alias.$right_fld" => null]);
				} else {
					$query->orWhere(["$table_alias.$right_fld" => null]);
				}
			} else {
				// Look for code and desc fields also
				$fields = array_unique(array_filter([$model->getModelInfo('code_field'), $model->getModelInfo('desc_field')]));
				if (count($fields)==1) {
					if ($is_and) {
						$query->andWhere(["$table_alias.$right_fld" => $value['v']]);
					} else {
						$query->orWhere(["$table_alias.$right_fld" => $value['v']]);
					}
				} else {
					$conds = ["or", [ "$table_alias.$right_fld" => $value['v']]];
					foreach ($fields as $fld) {
						if ($fld != $right_fld && $value['op'] != 'LIKE') {
							$conds[] = [ "LIKE", "$table_alias.$fld", $value['v'] ];
						}
					}
					if ($is_and) {
						$query->andWhere($conds);
					} else {
						$query->orWhere("or", $conds);
					}
				}
			}
		} else {
			if ($is_and) {
				$query->andWhere([$value['op'], "$table_alias.$attribute", $value['v'] ]);
			} else {
				$query->orWhere([$value['op'], "$table_alias.$attribute", $value['v'] ]);
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

	/**
	 * Sets a value in the grid filter row that can be parsed afterwards
	 */
	public function transformGridFilterValues()
	{
		return;
		foreach( array_merge(array_keys($this->normal_attrs), array_keys($this->related_properties)) as $attr ) {
			$value = $this->$attr;
			if ($value === null || $value === '') {
				continue;
			}
			if (!is_array($value)) {
				if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
					$value = json_decode($value, true);
				} else {
					$love = true;
				}
			}
			if ($value['v']!=='') {
				if ($value['op'] == '=') {
					if (!is_array($value['v'])) {
						$this->$attr = "=" . $value['v'];
					} else if (count($value['v'])==1) {
						$this->$attr = "=" . $value['v'][0];
					} else {
						$this->$attr = "IN('" . implode("','",$value['v']) . "')";
					}
				} else if (in_array($value['op'], ['<','>','<=','>=','<>'] ) ) {
					$this->$attr = $value['op'] . $value['v'];
				} else if( $value['op'] == 'LIKE' ) {
					$this->$attr = "LIKE '%{$value['v']}%'";
				}
			} else {
				$this->$attr = '';
			}
		}
	}

	protected function filterGlobal(&$query, array $attributes, string $value, $inclusiva = false)
	{
		if( $value === null || $value === '' ) {
			return;
		}
		$or_conds = [ 'OR' ];
		foreach( $attributes as $name => $operator) {
			if ($operator == null) {
				continue;
			}
			if (strpos($name, '.') === FALSE) {
				$relation = self::$relations[$name]??null;
				if (!$relation) {
					$fullfldname = $this->tableName() . "." . $name;
					if ($operator == "bool") {
						if (is_bool($value)) {
							$or_conds[] = [ $fullfldname => boolval($value) ];
						} else {
							continue;
						}
					} else {
						$or_conds[] = [ $operator, $fullfldname, $value ];
					}
					continue;
				} else {
					$attribute = '';
					$relation_name = $name;
					$relation = self::$relations[$relation_name]??null;
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
				$model_class = $relation['modelClass'];
				$model = $model_class::instance();
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

	private function addJoinIfNotExists($query, $aliases, $join_type, $tablename, $on): bool
	{
		if (!empty($query->join)) {
			foreach ($query->join as $join_def) {
				if ($join_def[1] == $tablename) {
					return false;
				}
			}
		}
		if (!empty($query->joinWith)) {
			foreach ($query->joinWith as $join_def) {
				if ($join_def[1] == $tablename) {
					return false;
				}
			}
		}
		foreach ($aliases as $alias => $tbl) {
			$on = preg_replace('/\b' . $tbl. '\./', $alias . '.', $on);
		}
		$query->join($join_type, $tablename, $on);
		return true;
	}

} // class
