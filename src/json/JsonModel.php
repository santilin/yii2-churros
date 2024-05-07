<?php

namespace santilin\churros\json;

use JsonPath\JsonObject;
use yii\base\{InvalidArgumentException,InvalidConfigException};
use santilin\churros\json\JsonModelable;

class JsonModel extends \yii\base\Model
// implements \yii\db\ActiveRecordInterface
{
    static $_parent_model_class;
    protected $parent_model;
    protected $_attributes = [];
    /** @var bool whether this is a new record */
    protected $_is_new_record = true;
    protected $_path = null;
    protected $_json_modelable = null;
    protected $_id = null;
    protected $_json_object = null;
    protected $_locator = null;

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
                $this->_json_object->set('$.' . $rel_info['relatedTablename'], $value);
            } else {
                throw new \Exception("error en tipo de relación en __set");
            }
            return;
        }
        return parent::__set($name, $value);
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        }
        if (isset(static::$relations[$name])) {
            $rel_info = static::$relations[$name];
            $rel_name = $rel_info['relatedTablename'];
            $rel_class = $rel_info['modelClass'];
            if ($rel_info['type'] == 'HasMany') {
                $json_objects = $this->_json_object->get("$.$rel_name")?:[];
                $related_models = [];
                foreach ($json_objects as $rm) {
                    if ($rm === null) {
                        continue;
                    }
                    $rel_model = new $rel_class;
                    if (is_string($rm)) {
                        $rel_model->setPrimaryKey($rm);
                    } else foreach ($rm as $fldname => $fldvalue) {
                        if ($rel_model->hasAttribute($fldname)) {
                            $rel_model->$fldname = $fldvalue;
                        }
                    }
                    $related_models[] = $rel_model;
                }
                return $related_models;
            } else {
                $rel_model = new $rel_class;
                $rel_model->loadJson($this->_json_modelable, $this->_path . "/$rel_name");
                return $rel_model;
            }
        }
        return parent::__get($name);
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
        $this->_path = $json_path . '/' . static::jsonPath();
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



} // class
