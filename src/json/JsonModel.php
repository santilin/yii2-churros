<?php

namespace santilin\churros\json;

use Yii;
use JsonPath\JsonObject;
use yii\base\{InvalidArgumentException,InvalidConfigException};
use yii\helpers\{ArrayHelper,StringHelper,Url};
use santilin\churros\json\JsonModelable;
use santilin\churros\models\ModelTracesTrait;


class JsonModel extends \yii\base\Model
// implements \yii\db\ActiveRecordInterface
{
    use ModelTracesTrait;

    static protected $parent_model_class;
    static protected $_locator = null;
    protected $_parent_model = null;
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

    public function jsonArrayToModels(array $json_array, string $child_class = null): array
    {
        $models = [];
        foreach ($json_array as $rk => $rm) {
            if (is_integer($rk) && ($rm === null || $rm === false)) {
                continue;
            }
            if (!$child_class) {
                $child_class = get_class($this);
                $child = new $child_class;
            } else {
                $child = new $child_class;
                $child->_parent_model = $this;
            }
            $child->_json_modelable = $this->_json_modelable;
            $child->setPath($this->getPath() . '/' . $child->jsonPath() . '/' . $child->_id);
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
            $child->afterFind();
            $models[] = $child;
        }
        return $models;
    }

    public function afterFind()
    {
        // trigger?
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
            $child->_parent_model = $this;
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

    public function parentModel($parent_id = null, $force = false): ?JsonModel
    {
        if (empty(static::$parent_model_class)) {
            return $this->_parent_model;
        }
        if ($this->_parent_model === null || $force) {
            if (!$this->_json_modelable) {
                throw new InvalidConfigException("Json model has no _json_modelable set");
            }
            if (!$this->_path) {
                return null;
            }
            // $locator = static::$_locator;
            // $id = $locator ? $this->$locator : $this->_id;
            // if ($id) {
            //     $id = str_replace('/',';',$id);
            //     if (!StringHelper::endsWith($this->_path, $id)) {
            //         $parts = explode('/', $this->_path . '/' . $id);
            //     } else {
            //         $parts = explode('/', $this->_path);
            //     }
            // } else {
            // }
            $parts = explode('/', $this->_path);
            if (in_array(\santilin\churros\models\ModelSearchTrait::class, class_uses($this))) {
                // array_pop($parts); // search part
            } else {
                if (count($parts)<2) {
                    return null;
                }
                array_pop($parts); // $this->_id
                array_pop($parts); // jsonPath()
            }
            if ($parent_id == null) {
                $parent_id = $parts[count($parts)-1];
            }
            $this->_parent_model = new static::$parent_model_class;
            if (!$this->_parent_model->loadJson($this->_json_modelable, implode('/', $parts), $parent_id)) {
                $this->_parent_model = null;
            }
        }
        return $this->_parent_model;
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

    static public function primaryKey()
    {
        $pk_fld = static::$_locator??static::$_model_info['code_field']??null;
        if ($pk_fld) {
            return [ $pk_fld ];
        } else {
            return [];
        }
    }

    public function setPrimaryKey($id = null)
    {
        $locator = static::$_locator??null;
        if ($id===null) {
            if (!empty(static::$_locator)) {
                $this->_id = $this->$locator;
            }
        } else if (is_array($id)) {
            foreach ($id as $id_k => $id_v) {
                $this->$id_k = $id_v;
                if ($locator) {
                    if ($id_k == $locator) {
                        $this->$id_k = $id_v;
                    }
                }
            }
        } else {
            $this->_id = $id;
            if ($locator) {
                $values = [ $locator => $id ];
                $this->setAttributes($values, false); // if safeonly, there can be recursion while getting scenarios
            }
        }
    }

    public function loadSearchModel(JsonModelable $json_modelable, string $json_path)
    {
        $this->_json_modelable = $json_modelable;
        $this->_path = $json_path;
        // if (StringHelper::endsWith($json_path, '/'. static::jsonPath())) {
        // $this->_path = $json_path;
        // } else {
        //     $this->_path = $json_path . '/'. static::jsonPath();
        // }
    }

	public function loadJson(JsonModelable $json_modelable, string $json_path = null, string $id = null, string $locator = null):?JsonObject
    {
        $this->_json_modelable = $json_modelable;
        if (substr($json_path,-1,1) == '/') {
            $json_path = substr($json_path, 0, -1);
        }
        $this->_path = $json_path;
        if ($locator === null) {
            $locator = static::$_locator;
        }
        if ($id) {
            if (!StringHelper::endsWith($this->_path, "/$id")) {
                $this->_path .= "/$id";
            }
            $id = str_replace(";", '/', $id);
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
                        $this->__set($fldname, $fldvalue); // prefer attributes over public properties
                    }
                }
            }
        }
        return $this->_json_object;
    }

    public function rootJsonGet(string $path)
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
                        $this->addTrace($err_message);
                        break;
                    case 'T:':
                        $this->addTrace($err_message);
                        break;
                    case 'W:':
                        $this->addWarning($k, $err_message);
                        $this->addTrace($err_message);
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
            $child->_parent_model = $this;
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
                $child->_parent_model = $this;
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

    public function createChildren(string $relation_name, string $form_class_name = null)
    {
        return $this->createRelatedModels($relation_name, [], $form_class_name);
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
            $relations_handled = [];
            foreach ($relations_in_model as $rel_name => $model_relation) {
                foreach ($relations_in_form as $rel_form_name => $rel_form_relation) {
                    if ($rel_form_name != $rel_name && $rel_form_relation != $rel_name) {
                        continue;
                    }
                    if (isset($relations_handled[$rel_form_relation])) {
                        continue;
                    }
                    $related_model_name = $model_relation['model'];
                    if ($model_relation['type'] == 'HasOne' || $model_relation['type'] == "OneToOne") {
                        // Look for embedded relations data in the main form
                        $post_data = null;
                        if( isset($post[$formName][$rel_name]) && is_array($post[$formName][$rel_name]) ) {
                            $post_data = $post[$formName][$rel_name];
                        } else if( isset($post[$formName][$related_model_name]) && is_array($post[$formName][$related_model_name]) ) {
                            $post_data = $post[$formName][$related_model_name];
                        } else if( isset($post[$related_model_name]) && is_array($post[$related_model_name]) ) {
                            $post_data = $post[$related_model_name];
                        }
                        if( $post_data ) {
                            $rel_model = new $model_relation['modelClass'];
                            $rel_model->setAttributes( $post_data );
                            $this->populateRelation($rel_name, $rel_model);
                            $relations_handled[$rel_form_relation] = true;
                        }
                    } else {
                        // HasMany or Many2Many outside of formName
                        $post_data = null;
                        if (is_string($rel_form_name) && isset($post[$formName][$rel_form_name]) && is_array($post[$formName][$rel_form_name])) {
                            $post_data = $post[$formName][$rel_form_name];
                        } else if (isset($post[$formName][$rel_form_relation]) && is_array($post[$formName][$rel_form_relation])) {
                            $post_data = $post[$formName][$rel_form_relation];
                        } else if (is_string($rel_form_name) && isset($post[$rel_form_name]) && is_array($post[$rel_form_name])) {
                            $post_data = $post[$rel_form_name];
                        } else if (isset($post[$rel_form_relation]) && is_array($post[$rel_form_relation])) {
                            $post_data = $post[$rel_form_relation];
                        } else if ($rel_form_name != $related_model_name && $rel_form_relation != $related_model_name) {
                            if (isset($post[$formName][$related_model_name]) && is_array($post[$formName][$related_model_name])) {
                                $post_data = $post[$formName][$related_model_name];
                            } else if (isset($post[$related_model_name]) && is_array($post[$related_model_name])) {
                                $post_data = $post[$related_model_name];
                            }
                        }
                        if ($post_data) {
                            $this->loadToRelation($rel_name, $post_data);
                            $relations_handled[$rel_form_relation] = true;
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }


    /**
     * Refactored from loadAll() function
     * @param string $rel_name
     * @param array $form_values
     * @return bool
     */
    public function loadToRelation(string $rel_name, array $post_data): void
    {
        /* @var $this JsonModel */
        /* @var $relObj JsonModel */
        /* @var $relModelClass string */
        $relation = static::$relations[$rel_name];
		$relModelClass = $relation['modelClass'];
		$container = [];
        $relPKAttr = [ $relModelClass::$_locator ?? 'id' ];
        if ($relation['type'] == 'HasMany') {
            foreach ($post_data as $form_values) {
                $relObj = new $relModelClass;
                $relObj->setJsonModelable($this);
                $relObj->_parent_model = $this;
                if (!is_array($form_values) ) {
                    $form_values = [$relPKAttr[0] => $form_values];
                }
                if (count($form_values)) {
                    $relObj->_attributes = array_intersect_key($form_values, $relObj->_attributes);
                    $relObj->afterFind();
                    $container[] = $relObj;
                }
            }
        } else if ($relation['type'] == 'ManyToMany') {
            foreach ($post_data as $form_values) {
				if( is_array($form_values) ) {
					$id = $form_values[$relPKAttr[0]];
					$relObj = empty($id) ? new $relModelClass : $relModelClass::findOne($id);
					$relObj->load($form_values);
				} else {
					$id = $form_values;
					$relObj = [ $relPKAttr[0] => $id ];
				}
				$container[] = $relObj;
			}
        }
        $this->_related[$rel_name] = $container;
    }

	public function handyFieldValues(string $field, string $format,
		string $model_format = 'medium', array|string|null $scope = [], ?string $filter_fields = null)
	{
		throw new \Exception("field '$field' not supported in " . get_called_class() . "::handyFieldValues() ");
	}

	public function setAttributesFromNoArray($any)
    {
    }


	protected function relationOfModel($related_model): ?string
	{
		$cn = $related_model->className();
		foreach (self::$relations as $relname => $rel_info) {
			if ($rel_info['modelClass'] == $cn) {
				return $relname;
			}
		}
		// If it's a derived class like *Form, *Search, look up its parent
		$cn = get_parent_class($related_model);
		foreach (self::$relations as $relname => $rel_info) {
			if ($rel_info['modelClass'] == $cn) {
				return $relname;
			}
		}
		return null;
	}

	public function linkDetails($detail, ?string $relation_name = null): void
	{
		if (!$relation_name) {
			$relation_name = $this->relationOfModel($detail);
		}
		if (!$relation_name) {
			Yii::warning("No relation between " . get_class($this)
				. " and " . get_class($detail));
			return;
		}
		$relation_getter = "get" . ucfirst($relation_name);
		$relation = $this->$relation_getter();
		if ($relation->via) { // many2many
			foreach ($relation->via[1]->link as $left_field => $right_field) {
				$params[$detail->formName()][$left_field] = $this->$right_field;
				// $detail->$left_field = $this->$right_field;
			}
			// $params['_search_relations'] = $relation_name;
		} else if ($relation->multiple) {
			foreach ($relation->link as $left_field => $right_field) {
				$params[$detail->formName()][$left_field] = $this->$right_field;
				$detail->$left_field = $this->$right_field;
			}
		}
	}

	public function linkToMe(string $format = 'long', string $action = 'view', bool $global = false, string $base_route = null): string
	{

		if ($base_route === null) {
			$base_route = Yii::$app->module?->id;
			if ($base_route) {
				$base_route .= '/';
			}
		}
		$link = $base_route . $this->pathToUrl($action);
        $url = Url::to([$link, 'id' => $this->getPrimaryKey()], $global);
		if ($format == false) {
			return $url;
		} else {
			return \yii\helpers\Html::a($this->recordDesc($format, 0), $url);
		}
	}



} // class
