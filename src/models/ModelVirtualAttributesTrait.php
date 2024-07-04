<?php

namespace santilin\churros\models;
use yii\base\InvalidArgumentException;

Trait ModelVirtualAttributesTrait
{
	public function __get($name)
	{
		if (array_key_exists($name, $this->_virtual_attributes)) {
			return $this->_virtual_attributes[$name];
		}
		return parent::__get($name);
	}

	public function __set($name, $value)
	{
		if (array_key_exists($name, $this->_virtual_attributes)) {
			$this->_virtual_attributes[$name] = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Returns attribute values.
	 * @param array|null $names list of attributes whose value needs to be returned.
	 * Defaults to null, meaning all attributes listed in [[attributes()]] will be returned.
	 * If it is an array, only the attributes in the array will be returned.
	 * @param array $except list of attributes whose value should NOT be returned.
	 * @return array attribute values (name => value).
	 */
	public function getVirtualAttributes($names = null, $except = [])
	{
		$values = [];
		if ($names === null) {
			$names = array_keys($this->_virtual_attributes);
		}
		foreach ($names as $name) {
			$values[$name] = $this->$name;
		}
		foreach ($except as $name) {
			unset($values[$name]);
		}

		return $values;
	}

	/**
	 * Sets the attribute values in a massive way.
	 * @param array $values attribute values (name => value) to be assigned to the model.
	 * @param bool $safeOnly whether the assignments should only be done to the safe attributes.
	 * A safe attribute is one that is associated with a validation rule in the current [[scenario]].
	 * @see safeAttributes()
	 * @see attributes()
	 */
	public function setVirtualAttributes($values, bool $fail_on_no_key = false)
	{
		if (is_array($values)) {
			$attributes = array_flip(array_keys($this->_virtual_attributes));
			foreach ($values as $name => $value) {
				if (isset($attributes[$name])) {
					$this->$name = $value;
				} if ($fail_on_no_key) {
					throw new InvalidArgumentException("$name: invalid virtual attribute name");
				}
			}
		}
	}


	public function resetVirtualAttributes($except = [])
	{
		$attributes = array_keys($this->_virtual_attributes);
		foreach ($this->_virtual_attributes as $k => $v) {
			if (count($except)== 0 || in_array($k, $except)) {
				$this->$k = null;
			}
		}
	}

}


