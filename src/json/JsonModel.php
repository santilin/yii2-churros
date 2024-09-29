<?php

namespace santilin\churros\json;

use JsonPath\JsonObject;
use yii\base\{InvalidArgumentException,InvalidConfigException};
use yii\helpers\{ArrayHelper,StringHelper};
use santilin\churros\json\JsonModelable;

class JsonModel extends \yii\base\Model
// implements \yii\db\ActiveRecordInterface
{
    static protected $parent_model_class;
    static protected $_locator = null;
    protected $parent_model = null;
    protected $_attributes = [];
    /** @var bool whether this is a new record */
    protected $_is_new_record = true;
    protected $_path = null;
    protected $_json_modelable = null;
    protected $_id = null;
    protected $_json_object = null;
    protected $_related = null;

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_attributes)) {
            $this->_attributes[$name] = $value;
            return;
        }
        if (isset(static::$relations[$name])) {
            $rel_info = static::$relations[$name];
            $rel_name = $rel_info['relatedTablename'];
            if ($rel_info['type'] == 'HasMany') {
                if ($this->_json_object) {
                    $this->_json_object->set("$.{$rel_info['relatedTablename']}", $value);
                } else {
                    $this->_json_modelable->setJsonObject('$' . $this->_path, $value??[],
                                                          $this->_id ?: $this->{static::$_locator}, null);
                }
            } else {
                throw new \Exception("error en tipo de relación en __set");
            }
            return;
        }
        return parent::__set($name, $value);
    }

    public function __isset($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            return true;
        } else {
            return parent::__isset($name);
        }
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        }
        if ($this->_related && array_key_exists($name, $this->_related) && $this->_related[$name] !== null) {
            return $this->_related[$name];
        }
        if (isset(static::$relations[$name])) {
            return $this->loadRelatedModels($name);
        }
        return parent::__get($name);
    }

    public function setJsonModelable(JsonModel $other)
    {
        $this->_json_modelable = $other->_json_modelable;
    }

    public function jsonArrayToModels(array $json_array, string $model_class): array
    {
        $models = [];
        foreach ($json_array as $rk => $rm) {
            if (is_integer($rk) && ($rm === null || $rm === false)) {
                continue;
            }
            $child = new $model_class;
            $child->parent_model = $this;
            $child->setPath($this->getPath() . '/' . $child->jsonPath());
            $child->setJsonModelable($this);
            $primary_key_set = false;
            if (is_array($rm)) {
                foreach ($rm as $fldname => $fldvalue) {
                    if ($child->hasAttribute($fldname)) {
                        if ($fldname == static::$_locator) {
                            $child->setPrimaryKey($fldvalue);
                            $primary_key_set = true;
                        } else {
                            $child->$fldname = $fldvalue;
                        }
                    }
                }
            } else {
                $child->setAttributesFromNoArray($rm);
            }
            if (!$primary_key_set) {
                if (is_string($rk)) {
                    $child->setPrimaryKey($rk);
                } else if (is_string($rm)) {
                    $child->setPrimaryKey($rm);
                }
            }
            $models[] = $child;
        }
        return $models;
    }

    public function loadRelatedModels(string $relation_name): array|JsonModel
    {
        $rel_info = static::$relations[$relation_name];
        $rel_name = $rel_info['relatedTablename'];
        $rel_model_class = $rel_info['modelClass'];
        if ($rel_info['type'] == 'HasMany') {
            $json_objects = $this->_json_object?->get("$.$rel_name")?:[];
            $related_models = $this->jsonArrayToModels($json_objects, $rel_model_class);
            return $related_models;
        } else {
            $child = new $rel_model_class;
            $child->parent_model = $this;
            $child->setPath($this->getPath() . '/' . $child->jsonPath());
            $child->loadJson($this->_json_modelable, $this->_path . "/$rel_name");
            return $child;
        }
    }

    public function locator(): string
    {
        return static::$_locator??'_id';
    }

    public function getIsNewRecord(): bool
    {
        return $this->_is_new_record;
    }

    public function setIsNewRecord(bool $is_new)
    {
        $this->_is_new_record = $is_new;
    }

    public function parentModel($parent_id = null): ?JsonModel
    {
        if (empty(static::$parent_model_class)) {
            $this->parent_model = null;
            return null;
        }
        if ($this->parent_model === null) {
            if (!$this->_json_modelable) {
                throw new InvalidConfigException("Json model has no _json_modelable set");
            }
            if (!$this->_path) {
                return null;
            }
            if (!StringHelper::endsWith($this->_path, $this->_id)) {
                $parts = explode('/', $this->_path . '/' . $this->_id);
            } else {
                $parts = explode('/', $this->_path);
            }
            if (count($parts)<2) {
                return null;
            }
            if (!in_array($parts[count($parts)-1], ['fields','behaviors','models'])) {
                array_pop($parts);
            }
            array_pop($parts);
            if ($parent_id == null) {
                $parent_id = $parts[count($parts)-1];
            }
            $this->parent_model = new static::$parent_model_class;
            if (!$this->parent_model->loadJson($this->_json_modelable, implode('/', $parts), $parent_id)) {
                $this->parent_model = null;
            }
        }
        return $this->parent_model;
    }

    public function setPath(string $path)
    {
        $this->_path = $path;
    }

    public function getPath(): string
    {
        return $this->_path;
    }

    public function fullPath(): string
    {
        if ($this->_id) {
            return $this->_path . '/' . $this->_id;
        } else {
            return $this->_path;
        }
    }

    public function getJsonObject(): ?JsonObject
    {
        return $this->_json_object;
    }

    public function attributes()
    {
        return array_keys($this->_attributes);
    }

    public function hasAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->_attributes);
    }

    public function setAttribute(string $name, $value)
    {
        $this->_attributes[$name] = $value;
    }

    public function getJsonId()
    {
        return $this->_id;
    }

    public function getPrimaryKey($asArray = false)
    {
        $pk_fld = static::$_locator??static::$_model_info['code_field']??null;
        if ($pk_fld) {
            if ($asArray) {
                return [ $pk_fld => $this->$pk_fld ];
            } else {
                return $this->$pk_fld;
            }
        } else if ($asArray) {
            return [ '_id' => $this->_id ];
        } else {
            return $this->_id;
        }
    }

    public function primaryKey()
    {
        $pk_fld = static::$_locator??static::$_model_info['code_field']??null;
        if ($pk_fld) {
            return [ $pk_fld ];
        } else {
            return [];
        }
    }

    public function setPrimaryKey($id)
    {
        if (is_array($id)) {
            foreach ($id as $id_k => $id_v) {
                $this->$id_k = $id_v;
                if (!empty(static::$_locator)) {
                    if ($id_k == static::$_locator) {
                        $this->$id_k = $id_v;
                    }
                }
            }
        } else {
            $this->_id = $id;
            if (!empty(static::$_locator)) {
                $values = [ static::$_locator => $id ];
                $this->setAttributes($values);
            }
        }
    }

    public function loadSearchModel(JsonModelable $json_modelable, string $json_path)
    {
        $this->_json_modelable = $json_modelable;
        if (StringHelper::endsWith($json_path, '/'. static::jsonPath())) {
            $this->_path = $json_path;
        } else {
            $this->_path = $json_path . '/'. static::jsonPath();
        }
    }

	public function loadJson(JsonModelable $json_modelable, string $json_path = null, string $id = null, string $locator = null):?JsonObject
    {
        $this->_json_modelable = $json_modelable;
        $this->_path = $json_path;
        if ($id && !StringHelper::endsWith($this->_path, $id)) {
            $this->_path .= "/$id";
        }
        if ($locator === null) {
            $locator = static::$_locator;
        }
        $this->_json_object = $json_modelable->getJsonObject($json_path, $id, $locator);
        if ($this->_json_object !== null) {
            $this->_is_new_record = false;
            $this->setPrimaryKey($id);
            $v = $this->_json_object->getValue();
            if ($v === null) {
            } else if (is_bool($v)) {
            } else if (is_string($v)) {
                if ($v != $id) {
                    throw new InvalidConfigException("$v != $id");
                }
            } else {
                foreach ($v as $fldname => $fldvalue) {
                    if ($this->hasAttribute($fldname)) {
                        $this->$fldname = $fldvalue;
                    }
                }
            }
        }
        return $this->_json_object;
    }

    /**
     * Load all attributes including related attributes
     * @param $post
     * @param array $relations_in_form
     * @return bool
     */
//     public function loadAll($post, $relations_in_form = [], $formName = null)
//     {
// 		return $this->load($post, $formName);
// 	}

    public function jsonGet(string $path)
    {
        return $this->_json_modelable->getJsonValue($path);
    }

	public function beginTransaction()
    {
        return $this;
    }

    public function rollBack()
    {
    }

    public function commit()
    {
    }

    public function save($runValidation = true, $validateFields = null)
    {
        throw new \Exception("Nothing to save from here");
    }

	public function delete()
	{
        throw new \Exception("Nothing to delete from here");
    }

    public function setJsonProperties($json_object)
    {
        $props = $json_object->get('$');
        foreach ($props as $pk => $prop) {
            $this->$pk = $prop;
        }
    }

    public function addErrorsAndWarnings(array $err)
    {
        foreach ($err as $k => $err_message) {
            if (strlen($err_message)>2) {
                switch (substr($err_message,0,2)) {
                    case 'E:':
                        $this->addError($k, $err_message);
                        break;
                    case 'T:':
                    case 'W:':
                        $this->addWarning($k, $err_message);
                    default:
                }
            }
        }
    }

    public function createChild(string $rel_name, string $form_class_name = null)
    {
        if (isset(static::$relations[$rel_name])) {
            $r = static::$relations[$rel_name];
            $rel_model_class = $r['modelClass'];
            if ($form_class_name != null) {
                $child = new $form_class_name;
                if (!($child instanceof $rel_model_class)) {
                    throw new InvalidConfigException("$form_class_name is not derived from $rel_model_class");
                }
            } else {
                $child = new $rel_model_class;
            }
            $child->parent_model = $this;
            $child->setPath($this->getPath() . '/' . $child->jsonPath());
            $child->setJsonModelable($this);
            return $child;
        } else {
            return null;
        }
    }

    public function createRelatedModels(string $relation_name,
        array $current_values = [], string $form_class_name = null): array|JsonModel
    {
        $rel_info = static::$relations[$relation_name];
        $rel_model_class = $rel_info['modelClass'];
        if (!$form_class_name || $rel_model_class == $form_class_name) {
            return $this->$relation_name;
        }
        $child = new $rel_model_class;
        if (!($child instanceof $rel_model_class)) {
            throw new InvalidConfigException("$form_class_name is not derived from $rel_model_class");
        }
        if ($rel_info['type'] == 'HasMany') {
            $related_models = [];
            foreach ($this->$relation_name as $kr => $rel_model) {
                $child = new $form_class_name;
                $child->parent_model = $this;
                $child->setPath($this->getPath() . '/' . $child->jsonPath());
                $child->setJsonModelable($this);
                $child->copy($rel_model);
                $related_models[$kr] = $child;
            }
            return $related_models;
        } else {
            $child->setPath($this->getPath() . '/' . $child->jsonPath());
            $child->setJsonModelable($this);
            $child->copy($this->$relation_name);
            return $child;
        }
    }


    /**
     * Load all attributes including related attributes
     * @param $post
     * @param array $relations_in_form
     * @return bool
     */
    public function loadAll(array $post, array $relations_in_form = [], ?string $formName = null): bool
    {
        if( $formName === null ) {
			$formName = $this->formName();
		}
        if ($this->load($post, $formName)) {
			if (count($relations_in_form)==0) {
				return true;
			}
            $relations_in_model = static::$relations;
            foreach($relations_in_model as $rel_name => $model_relation ) {
				if( !in_array($rel_name, $relations_in_form) ) {
					continue;
				}
				if( $model_relation['type'] == 'HasOne' || $model_relation['type'] == "OneToOne" ) {
					// Look for embedded relations data in the main form
					$post_data = null;
					if( isset($post[$formName][$rel_name]) && is_array($post[$formName][$rel_name]) ) {
						$post_data = $post[$formName][$rel_name];
					} else if( isset($post[$formName][$model_relation['model']]) && is_array($post[$formName][$model_relation['model']]) ) {
						$post_data = $post[$formName][$model_relation['model']];
					} else if( isset($post[$model_relation['model']]) && is_array($post[$model_relation['model']]) ) {
						$post_data = $post[$model_relation['model']];
					}
					if( $post_data ) {
						$rel_model = new $model_relation['modelClass'];
						$rel_model->setAttributes( $post_data );
						$this->populateRelation($rel_name, $rel_model);
					}
				} else {
                    // HasMany or Many2Many outside of formName
					$post_data = (isset($post[$rel_name]) && is_array($post[$rel_name]))
						? $post[$rel_name] : null;
					if( $post_data === null ) {
						$post_data = (isset($post[$formName][$rel_name]) && is_array($post[$formName][$rel_name])) ? $post[$formName][$rel_name] : null ;
					}
					if( $post_data === null ) {
						$post_data = (isset($post[$model_relation['model']]) && is_array($post[$model_relation['model']])) ? $post[$model_relation['model']] : null ;
					}
					if( $post_data ) {
                        $this->loadToRelation($rel_name, $post_data);
                    }
                }
            }
            return true;
        }
        return false;
    }


    /**
     * Refactored from loadAll() function
     * @param $relation skimmed relation data
     * @param $rel_name
     * @param $v form values
     * @return bool
     */
    public function loadToRelation($rel_name, $v)
    {
        /* @var $this ActiveRecord */
        /* @var $relObj ActiveRecord */
        /* @var $relModelClass ActiveRecord */
        $relation = static::$relations[$rel_name];
		$relModelClass = $relation['modelClass'];
		$container = [];
        $relPKAttr = [ $relModelClass::$_locator ?? 'id' ];
        if ($relation['type'] == 'HasMany') {
            foreach ($v as $relPost) {
                $relObj = new $relModelClass();
                if (is_array($relPost) ) {
                    if (array_filter($relPost)) {
                        foreach ($relObj->_attributes as $ka => $av) {
                            if (!array_key_exists($ka, $relPost)) {
                                unset($relObj->_attributes[$ka]);
                            } else {
                                $relObj->_attributes[$ka] = $relPost[$ka];
                            }
                        }
                        $container[] = $relObj;
                    }
                } else {
                    // Just primary key of records, just one field in primary key
                    $relObj->load([ $relPKAttr[0] => $relPost ], '');
                    $container[] = $relObj;
                }
            }
        } else if ($relation['type'] == 'ManyToMany') {
			foreach( $v as $relPost ) {
				if( is_array($relPost) ) {
					$id = $relPost[$relPKAttr[0]];
					$relObj = empty($id) ? new $relModelClass : $relModelClass::findOne($id);
					$relObj->load($relPost);
				} else {
					$id = $relPost;
					$relObj = [ $relPKAttr[0] => $id ];
				}
				$container[] = $relObj;
			}
        }
// 		$this->populateRelation($rel_name, $container);
        $this->_related[$rel_name] = $container;
        return true;
    }

	public function handyFieldValues(string $field, string $format,
		string $model_format = 'medium', array|string $scope=null, ?string $filter_fields = null)
	{
		throw new \Exception("field '$field' not supported in " . get_called_class() . "::handyFieldValues() ");
	}

	public function setAttributesFromNoArray($any)
    {
    }

} // class
