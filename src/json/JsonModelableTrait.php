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
		if ($locator && $id) {
			if (AppHelper::lastWord($path, '/') == $id) {
				$path = AppHelper::removeLastWord($path, '/');
			}
			$ret = $this->_json_root->getJsonObjects('$' . str_replace('/','.',$path)
				. "[?(@.$locator=='$id')]");
			if (is_array($ret) && isset($ret[0])) {
				return $ret[0];
			} else {
				return null;
			}
		} else {
			$ret = $this->_json_root->getJsonObjects('$' . str_replace('/','.',$path) . ($id?('.' . $id):''));
			if ($ret) {
				return $ret;
			} else {
				return null;
			}
		}
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
