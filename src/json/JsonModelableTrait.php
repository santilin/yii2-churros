<?php

namespace santilin\churros\json;
use yii\base\InvalidConfigException;
use santilin\churros\Helpers\AppHelper;
use JsonPath\JsonObject;

trait JsonModelableTrait
{
	/** @var JsonPath\JsonObject the root of the json content */
	protected $_root_json = false;

	public function getJsonObject(string $path, ?string $id, ?string $locator=null): ?JsonObject
	{
		if ($this->_root_json === false) {
			throw new InvalidConfigException("getJsonValue::_root_json == null");
		}
		if ($id && AppHelper::lastWord($path, '/') == $id) {
			$path = AppHelper::removeLastWord($path, '/');
		}
		if ($id) { // The id takes precedence over the locator
			$ret = $this->_root_json->getJsonObjects('$' . str_replace('/','.',$path)
				. "['$id']");
			if ($ret !== false) {
				return $ret;
			}
			$ret = $this->_root_json->getJsonObjects('$' . str_replace('/','.',$path)
				. "[?(@=='$id')]");
			if (is_array($ret) && isset($ret[0])) {
				return $ret[0];
			}
		}
		if ($locator && $id) {
			$ret = $this->_root_json->getJsonObjects('$' . str_replace('/','.',$path)
				. "[?(@.$locator=='$id')]");
			if ($ret === false) {
				$ret = $this->_root_json->getJsonObjects('$' . str_replace('/','.',$path)
				. "[?(@=='$id')]");
			}
			if (is_array($ret) && isset($ret[0])) {
				return $ret[0];
			}
		}
		if ($id) {
			return null;
		}
		$ret = $this->_root_json->getJsonObjects('$' . str_replace('/','.',$path));
		if ($ret) {
			return $ret;
		} else {
			return null;
		}
	}

	public function setJsonObject(string $path, mixed $value, ?string $id, ?string $locator=null)
	{
		if ($this->_root_json === false) {
			throw new InvalidConfigException("getJsonValue::_root_json == null");
		}
		if (AppHelper::lastWord($path, '/') == $id) {
			$path = AppHelper::removeLastWord($path, '/');
		}
		$path = str_replace('/', '.', $path);
		if ($locator && $id) {
			$set_path = $path . "[?(@.{$locator}=='$id')]";
		} else if ($locator) {
			$set_path = $path . "[?(@.{$locator}=='" . $this->$locator . "')]";
		} else if ($id) {
			$set_path = $path . '.'. $id;
		}
		$this->_root_json->set($set_path, $value);
	}

	public function getJsonArray(string $path, ?string $id, ?string $locator=null): array
	{
		if ($this->_root_json === false) {
			throw new InvalidConfigException("getJsonValue::_root_json == null");
		}
		if ($locator && $id) {
			$ret = $this->_root_json->get('$' . str_replace('/','.',$path)
				. "[?(@.$locator=='$id')]");
			if (is_array($ret)) {
				return $ret[0];
			} else {
				return $ret;
			}
		} else {
			$ret = $this->_root_json->get('$' . str_replace('/','.',$path) . ($id?('.' . $id):''));
			if (!empty($ret)) {
				return $ret;
			} else {
				return [];
			}
		}
	}

	public function getJsonValue(string $path)
	{
		if ($this->_root_json === false) {
			throw new InvalidConfigException("getJsonValue::_root_json == null");
		}
		return $this->_root_json->get($path);
	}

}
