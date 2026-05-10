<?php

namespace santilin\churros\json;
use yii\base\InvalidConfigException;
use santilin\churros\Helpers\AppHelper;
use JsonPath\JsonObject;

trait JsonModelableTrait
{
	/** @var JsonPath\JsonObject the root of the json content */
	protected $_root_json = null;

	public function getJsonObject(string $path, ?string $id, ?string $locator=null): ?JsonObject
	{
		if ($this->_root_json === null) {
			throw new InvalidConfigException("getJsonValue::_root_json == null");
		}
		$path_parts = array_filter(static::pathParts($path));
		$path = implode('.', $path_parts);
		if ($id) { // The id takes precedence over the locator
			$ret = $this->_root_json->getJsonObjects("$.{$path}['$id']");
			if (is_array($ret) && isset($ret[0])) {
				return $ret[0];
			}
			if ($ret !== false) {
				return $ret;
			}
			$ret = $this->_root_json->getJsonObjects("$.{$path}[?(@=='$id')]");
			if (is_array($ret) && isset($ret[0])) {
				return $ret[0];
			}
		}
		if ($locator && $id) {
			$ret = $this->_root_json->getJsonObjects("$.{$path}[?(@.$locator=='$id')]");
			if ($ret === false) {
				$ret = $this->_root_json->getJsonObjects("$.{$path}[?(@=='$id')]");
			}
			if (is_array($ret) && isset($ret[0])) {
				return $ret[0];
			}
		}
		if (!$id) {
			return null;
		}
		$ret = $this->_root_json->getJsonObjects("$.path");
		if ($ret) {
			return $ret;
		} else {
			return null;
		}
	}

	public function setJsonObject(string $path, mixed $value, ?string $id, ?string $locator=null)
	{
		if ($this->_root_json === null) {
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

	public function getJsonArray(string $path): array
	{
		if ($this->_root_json === null) {
			throw new InvalidConfigException("getJsonValue::_root_json == null");
		}
		$ret = $this->_root_json->get('$' . str_replace('/','.',$path));
		if (!empty($ret)) {
			return $ret;
		} else {
			return [];
		}
	}

	public function getJsonValue(string $path)
	{
		if ($this->_root_json === null) {
			throw new InvalidConfigException("getJsonValue::_root_json == null");
		}
		return $this->_root_json->get($path);
	}

	public static function pathParts(string $str): array
	{
		$result = [];
		$buffer = '';
		$in_single_quote = false;
		$in_bracket = false;

		$len = strlen($str);
		for ($i = 0; $i < $len; $i++) {
			$c = $str[$i];

			// Toggle single quote state
			if ($c === "'" && !($i > 0 && $str[$i-1] === '\\')) {
				$in_single_quote = !$in_single_quote;
			} else if ($c === '[' && !$in_single_quote) {
				// Split on [ when outside quotes
				$in_bracket = true;
				$result[] = $buffer;
				$buffer = '';
			} elseif ($c === ']' && $in_bracket && !$in_single_quote) {
				$in_bracket = false;
			}
			elseif ($c === '/' && !$in_single_quote) {
				// Split on / when outside quotes
				$result[] = $buffer;
				$buffer = '';
			} else {
				$buffer .= $c;
			}
		}

		// Last chunk
		if (!empty($buffer)) {
			$cleanPart = static::cleanPathPart(trim($buffer));
			if ($cleanPart !== '') {
				$result[] = $cleanPart;
			}
		}

		return $result;
	}

	private static function cleanPathPart(string $part): string
	{
		$part = trim($part);
		// Handle "['Tarea']" → "Tarea"
		if (strlen($part) > 4 &&
			$part[0] === "'" && $part[1] === '[' &&
			substr($part, -2) === "']") {
			return substr($part, 2, -2);
			}
			return $part;
	}


}
