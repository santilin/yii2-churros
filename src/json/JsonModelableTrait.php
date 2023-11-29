<?php

namespace santilin\churros\json;
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
			$ret = $this->_json_root->getJsonObjects('$' . str_replace('/','.',$path)
				. "[?(@.$locator=='$id')]");
			if (is_array($ret)) {
				return $ret[0];
			} else {
				return $ret;
			}
		} else {
			$ret =$this->_json_root->getJsonObjects('$' . str_replace('/','.',$path));
			if (is_array($ret) && $id ) {
				return $ret[$id]??null;
			} else {
				return $ret;
			}
		}
	}


}
