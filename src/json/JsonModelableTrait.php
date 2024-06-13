<?php

namespace santilin\churros\json;
use santilin\churros\Helpers\AppHelper;
use JsonPath\JsonObject;

trait JsonModelableTrait
{
	/** @var JsonPath\JsonObject the root of the json content */
	protected $_json_root = false;

	public function getJsonObject(string $path, ?string $id, ?string $locator=null): ?JsonObject
	{
		if ($this->_json_root === false) {
			$this->_json_root = $this->createJsonRoot();
		}
		if (AppHelper::lastWord($path, '/') == $id) {
			$path = AppHelper::removeLastWord($path, '/');
		}
		if ($locator && $id) {
			$ret = $this->_json_root->getJsonObjects('$' . str_replace('/','.',$path)
				. "[?(@.$locator=='$id')]");
			if (is_array($ret) && isset($ret[0])) {
				return $ret[0];
			}
		}
		if ($id) {
			$ret = $this->_json_root->getJsonObjects('$' . str_replace('/','.',$path)
				. ".$id");
			return $ret;
		}
		$ret = $this->_json_root->getJsonObjects('$' . str_replace('/','.',$path) . ($id?('.' . $id):''));
		if ($ret) {
			return $ret;
		} else {
			return null;
		}
	}

	public function setJsonObject(string $path, mixed $value, ?string $id, ?string $locator=null)
	{
		if ($this->_json_root === false) {
			$this->_json_root = $this->createJsonRoot();
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
		$this->_json_root->set($set_path, $value);
	}

	public function getJsonArray(string $path, ?string $id, ?string $locator=null): array
	{
		if ($this->_json_root === false) {
			$this->_json_root = $this->createJsonRoot();
		}
		if ($locator && $id) {
			$ret = $this->_json_root->get('$' . str_replace('/','.',$path)
				. "[?(@.$locator=='$id')]");
			if (is_array($ret)) {
				return $ret[0];
			} else {
				return $ret;
			}
		} else {
			$ret = $this->_json_root->get('$' . str_replace('/','.',$path) . ($id?('.' . $id):''));
			if (!empty($ret)) {
				return $ret;
			} else {
				return [];
			}
		}
	}

	public function getJsonValue(string $path)
	{
		if ($this->_json_root === false) {
			$this->_json_root = $this->createJsonRoot();
		}
		return $this->_json_root->get($path);
	}

}
