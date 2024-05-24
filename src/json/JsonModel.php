<?php

namespace santilin\churros\json;

use JsonPath\JsonObject;
use yii\base\{InvalidArgumentException,InvalidConfigException};
use santilin\churros\json\JsonModelable;
use santilin\churros\helpers\AppHelper;

class JsonModel extends \yii\base\Model
// implements \yii\db\ActiveRecordInterface
{
    static public $parent_model_class;
    protected $parent_model;
    protected $_attributes = [];
    /** @var bool whether this is a new record */
    protected $_is_new_record = true;
    protected $_path = null;
    protected $_json_modelable = null;
    protected $_id = null;
    protected $_json_object = null;
    protected $_locator = null;
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
                                                          $this->_id ?: $this->{$this->_locator}, null);
                }
            } else {
                throw new \Exception("error en tipo de relación en __set");
            }
            return;
        }
        return parent::__set($name, $value);
    }

    public function setJsonModelable(JsonModel $other)
    {
        $this->_json_modelable = $other->_json_modelable;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        }
        if (isset(static::$relations[$name])) {
            return $this->findRelatedModels($name);
        }
        return parent::__get($name);
    }

    public function findRelatedModels(string $relation_name, string $form_class_name = null): array|JsonModel
    {
        $rel_info = static::$relations[$relation_name];
        $rel_name = $rel_info['relatedTablename'];
        $rel_model_class = $rel_info['modelClass'];
        if ($rel_info['type'] == 'HasMany') {
            $json_objects = $this->_json_object?->get("$.$rel_name")?:[];
            $related_models = [];
            foreach ($json_objects as $rm) {
                if ($rm === null) {
                    continue;
                }
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
                if (is_string($rm)) {
                    $child->setPrimaryKey($rm);
                } else foreach ($rm as $fldname => $fldvalue) {
                    if ($child->hasAttribute($fldname)) {
                        $child->$fldname = $fldvalue;
                    }
                }
                $related_models[] = $child;
            }
            return $related_models;
        } else {
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
            $child->loadJson($this->_json_modelable, $this->_path . "/$rel_name");
            return $child;
        }
    }

    public function locator(): string
    {
        return $this->_locator;
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
        if (!$this->_json_modelable) {
            throw new InvalidConfigException("Json model has no _json_modelable defined");
        }
        if (!$this->_path) {
            return null;
        }
        $parts = explode('/', $this->_path);
        if (count($parts)<1) {
            return null;
        }
        array_pop($parts);
        $this->parent_model = new static::$parent_model_class;
        if ($this->parent_model->loadJson($this->_json_modelable, implode('/', $parts), $parent_id)) {
            return $this->parent_model;
        } else {
            return null;
        }

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
        return $this->_path;
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
        $code_fld = static::$_model_info['code_field'];
        if ($code_fld) {
            if ($asArray) {
                return [ $code_fld => $this->$code_fld ];
            } else {
                return $this->$code_fld;
            }
        } else if ($asArray) {
            return [ 'id' => $this->_id ];
        } else {
            return $this->_id;
        }
    }

    public function primaryKey()
    {
        $code_fld = static::$_model_info['code_field'];
        if ($code_fld) {
            return [ $code_fld ];
        } else {
            return [];
        }
    }

    public function setPrimaryKey($id)
    {
        $this->_id = $id;
        $code_fld = static::getModelInfo('code_field');
        if ($code_fld) {
            $this->$code_fld = $id;
        }
    }

    public function loadSearchModel(JsonModelable $json_modelable, string $json_path)
    {
        $this->_json_modelable = $json_modelable;
        if (AppHelper::endsWith($json_path, '/'. static::jsonPath())) {
            $this->_path = $json_path;
        } else {
            $this->_path = $json_path . '/'. static::jsonPath();
        }
    }

	public function loadJson(JsonModelable $json_modelable, string $json_path = null, string $id = null, string $locator = null):?JsonObject
    {
        $this->_json_modelable = $json_modelable;
        $this->_path = $json_path;
        if ($locator === null) {
            $locator = $this->_locator;
        }
        $this->_json_object = $json_modelable->getJsonObject($json_path, $id, $locator);
        if ($this->_json_object) {
            $this->_is_new_record = false;
            $v = $this->_json_object->getValue();
            if ($v === null) {
            } else if (is_string($v)) {
                if ($v != $id) {
                    throw new InvalidConfigException("$v != $id");
                }
            } else foreach ($v as $fldname => $fldvalue) {
                if ($this->hasAttribute($fldname)) {
                    $this->$fldname = $fldvalue;
                }
            }
            $this->setPrimaryKey($id);
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
        return $this->_json_modelable->get($path);
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

    public function save(bool $runValidation)
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
        $relPKAttr = [ $this->_locator ?? 'id' ];
        if ($relation['type'] == 'HasMany') {
            foreach ($v as $relPost) {
                if (is_array($relPost) ) {
                    if( array_filter($relPost) ) {
                        /* @var $relObj ActiveRecord */
//                         $relObj = (empty($relPost[$relPKAttr[0]])) ? new $relModelClass() : $relModelClass::findOne($relPost[$relPKAttr[0]]);
//                         if (is_null($relObj)) {
                            $relObj = new $relModelClass();
//                         }
                        $relObj->load($relPost, '');
                        $container[] = $relObj;
                    }
                } else {
                    // Just primary key of records, just one field in primary key
                    $container[] = [ $relPKAttr[0] => $relPost ];
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

} // class
