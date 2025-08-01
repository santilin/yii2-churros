<?php
namespace santilin\churros\models;

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
	use ModelTrait;

	public $_gridPageSize = 12;

	public function __get($name)
	{
		// GridView::renderFilter: needs activeAttribute when related property
		if (property_exists($this, 'related_properties')) {
			if (array_key_exists($name, $this->related_properties)) {
				if (!isset(static::$relations[$name])) {
					return $this->related_properties[$name];
				} else if (empty($this->related_properties[$name])) {
					// if (array_key_exists($name, $this->relatedRecords) && empty($this->relatedRecords['name'])) {
					// 	unset($this->relatedRecords['name']);
					// }
					return parent::__get($name);
				} else {
					return $this->related_properties[$name];
				}
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

    public function operatorForAttr(?string $rel_name, string $attr): string
	{
		$op = $this->related_operators[$rel_name]??false;
		if ($op === false) {
			$op = $this->normal_attrs[$rel_name]??false;
		}
		if ($op !== false) {
			return $op;
		}
		if ($rel_name && array_key_exists($rel_name, $this->related_properties)) {
			return '=';
		} else if (array_key_exists($attr,$this->related_properties)) {
			return '=';
		} else if ($attr == 'id') {
			return '=';
		} else {
			return 'LIKE';
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
				Yii::warning("No searchable: $col_attribute != " . ($column_def['attribute']??null));
				continue; // No searchable
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
						if ($sort_fldname != 'id' && isset($related_model_provider->sort->attributes[$sort_fldname]) ) {
							$related_sort = $related_model_provider->sort->attributes[$sort_fldname];
							if (isset($related_sort['label'])) {
								$new_related_sort = [ 'label' => $related_sort['label']];
								unset($related_sort['label']);
							} else {
								$new_related_sort = [];
							}
							foreach ($related_sort as $asc_desc => $sort_def) {
								foreach( $sort_def as $key => $value ) {
									$new_related_sort[$asc_desc]
									= [ $table_alias.".".$key => $value ];
								}
							}
							$provider->sort->attributes[$attribute] = $new_related_sort;
						} else {
							$related_default_order = $related_model_provider->sort->defaultOrder;
							if (!$related_default_order) {
								$related_default_order = [ $related_model->findCodeField() => SORT_ASC ];
							}
							$sort_attributes = [];
							foreach ($related_default_order as $sort_attribute => $sort_direction) {
								if (isset($related_model_provider->sort->attributes[$sort_attribute])) {
									foreach ($related_model_provider->sort->attributes[$sort_attribute]['asc'] as $fld_asc => $fld_asc_direction) {
										$provider->sort->attributes[$attribute]['asc'][$fld_asc] = $fld_asc_direction;
									}
									foreach ($related_model_provider->sort->attributes[$sort_attribute]['desc'] as $fld_desc => $fld_desc_direction) {
										$provider->sort->attributes[$attribute]['desc'][$fld_desc] = $fld_desc_direction;
									}
								}
							}
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
		while (strpos($attribute, '.') !== FALSE || isset($model::$relations[$attribute])) {
			if (strpos($attribute, '.') === FALSE) {
				$relation_name = $attribute;
				$attribute = '';
			} else {
				list($relation_name, $attribute) = AppHelper::splitString($attribute, '.');
			}
			$relation = $model::$relations[$relation_name]??null;
			if ($relation) {
				if ($relation['type'] == 'ManyToMany') {
					$junction_relation = $model::$relations[$relation['join']];
					$junction_model_class = $junction_relation['modelClass'];
					$junction_model = $junction_model_class::instance();
					$junction_table_alias = $table_alias . '_join_' . $relation_name;
					$nested_tablename = str_replace(['{{%', '{{', '}}'], '', $junction_model->tableName());
					$nested_relations[$junction_table_alias] = $nested_tablename; // $junction_relation['relatedTablename'];
					/// @todo $final_attribute when nested
					$this->addJoinIfNotExists($query, $nested_relations, "LEFT JOIN", [ $junction_table_alias => $nested_tablename], $junction_relation['join']);
					$related_table_alias = $junction_table_alias . '_' . $relation_name;
					$nested_relations[$related_table_alias] = $relation['relatedTablename'];
					$model_class = $relation['modelClass'];
					$model = $model_class::instance();
					foreach ($junction_model::$relations as $nr => $related_relation) {
						if ($related_relation['modelClass'] == $model_class) {
							break;
						}
					}
					if ($this->addJoinIfNotExists($query, $nested_relations, "LEFT JOIN", [ $related_table_alias => $model->tableName()], $related_relation['join'])) {
						if (!$attribute || $attribute == $relation_name) { // no related field set
							/// @todo findCodeAndDescFields
							list(, $final_attribute) = AppHelper::splitFieldName($related_relation['right']);
							$table_alias = $related_table_alias;
						} else {
							$final_attribute = $attribute;
							$table_alias = $related_table_alias;
						}
					} else {
						$final_attribute = $relation['other'];
						$table_alias = $nested_tablename;
					}
				} else {
					$table_alias = $relation_name;
					$model_class = $relation['modelClass'];
					$model = $model_class::instance();
					$nested_relations[$table_alias] = $relation['relatedTablename'];
					$this->addJoinIfNotExists($query, $nested_relations, "LEFT JOIN", [ $table_alias => $model->tableName()], $relation['join']);
					$final_attribute = $attribute;
				}
			} else {
				throw new InvalidArgumentException($relation_name . ": relation not found in model " . self::class . ' (SearchModel::filterWhereRelated)');
			}
		}
		return [$final_attribute??$attribute,$table_alias == 'as' ? '':$table_alias,$model,$relation];
	}


	protected function filterWhereRelated($query, $relation_name, $value): array
	{
		if ($value === null || $value === '' ||
			(is_array($value) && (!isset($value['v']) || (isset($value['v']) && $value['v']==='')))) {
			return [];
		}
		$conds = [];
		list($attribute, $table_alias, $model, $relation) = $this->addRelatedFieldToJoin($relation_name, $query);

		$value = FormHelper::toOpExpression($value, false, $this->operatorForAttr($relation_name, $attribute) );
		if ($attribute == '') {
			$search_flds = $model->findCodeAndDescFields();
			$rel_conds = [ 'OR' ];
			foreach ($search_flds as $search_fld) {
				$operator = $this->operatorForAttr(null, $search_fld);
				if (is_array($value['v'])) {
					$fld_conds = [ 'OR' ];
					foreach ($value['v'] as $v) {
						$fld_conds[] = [ $operator, "$table_alias.$search_fld", $v];
					}
					$rel_conds[] = $fld_conds;
				} else {
					$rel_conds[] = [ $operator, "$table_alias.$search_fld", $value['v']];
				}
			}
			return $rel_conds;
		} else {
			return [$value['op'], "$table_alias.$attribute", $value['v'] ];
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
		foreach (array_merge(array_keys($this->normal_attrs), array_keys($this->related_properties)) as $attr ) {
			if (array_key_exists($attr, $this->related_properties)) {
				$value = $this->related_properties[$attr];
			} else {
				$value = $this->$attr;
			}
			if ($value === null || $value === '' || $value === [] || is_object($value)) {
				continue;
			}
			if (!is_array($value)) {
				if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
					$value = json_decode($value, true);
				} else {
					continue;
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
					if (strtoupper($operator) == "BOOL") {
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

	/// Replaces table names with aliases
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
			$tbl_parts = explode('.', $tbl);
			if (count($tbl_parts) == 2) { // if database is present, there is no ambiguity
				$on = preg_replace('/\b' . $tbl. '\./', $alias . '.', $on);
			} else { // do not replace $tbl if it is a tablaname
				$on_parts = explode('=', $on);
				foreach ($on_parts as $on_k => $on_part) { // @todo @tbl.@tbl
					if (preg_match("/\b$tbl.\w+\./", $on_part)) {
						continue;
					} else {
						$on_parts[$on_k] = preg_replace('/\b' . $tbl. '\./', $alias . '.', $on_part);
					}
				}
				$on = implode('=', $on_parts);
			}
		}
		$query->join($join_type, $tablename, $on);
		return true;
	}

} // class
