<?php
/**
 * @link
 * @copyright
 * @license
 */

namespace santilin\churros\helpers;

use Yii;

class AppHelper
{
	static private $tabindex = 0;

    static public function empty($value)
    {
		return empty($value);
	}

	static public function endsWith($stack, $needle)
	{
		return substr($stack, -strlen($needle)) == $needle;
	}

	static public function startsWith($stack, $needle)
	{
		return substr($stack, 0, strlen($needle)) == $needle;
	}

	static public function getAppLocaleLanguage()
	{
		return str_replace("-", "_", Yii::$app->language);
	}

	/**
	 * @params string $route if null, the model controller
	 */
	static public function joinModels($glue, $models, $controller)
	{
		if( $models == null || count($models)==0 ) {
			return "";
		}
		$attrs = [];
		$route = null;
		foreach((array)$models as $model) {
			if( $route == null ) {
				$route = $controller->controllerRoute($model);
			}
			if( $model != null ) {
				$url = $route . strval($model->getPrimaryKey());
				$attrs[] = "<a href='$url'>" .  $model->recordDesc() . "</a>";
			}
		}
		return join($glue, $attrs);
	}

	/**
	* Strip the namespace from the class to get the actual class name
	*
	* @param string $obj Class name with full namespace
	*
	* @return string
	* @access public
	*/
	static public function stripNamespaceFromClassName($obj)
	{
		$classname = get_class($obj);
		if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
			$classname = $matches[1];
		}
		return $classname;
	}

	public static function camelCase($str, array $noStrip = [])
	{
		// non-alpha and non-numeric characters become spaces
		$str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', trim($str));
		// uppercase the first character of each word
		$str = ucwords($str);
		$str = str_replace(" ", '', $str);
		return $str;
	}

	/**
	 * Concatenates the values of each element of an array
	 */
	static public function concatArrayValues($array, $keyname, $valuesname, $delimiter = ", ")
	{
		$ret = [];
		foreach( $array as $element ) {
			$key = $value = '';
			foreach ( $element as $element_key => $element_value ) {
				if( $element_key == $keyname ) {
					$key = $element_value;
				} else {
					if( $value != '') {
						$value .= $delimiter;
					}
					$value .= $element_value;
				}
			}
			$ret[$key] = $value;
		}
		return $ret;
	}

	static public function ti($inc=1)
	{
		static::$tabindex += $inc;
		return static::$tabindex;
	}

	static public function resetTabIndex($reset = 0)
	{
		static::$tabindex = $reset;
	}

    static public function mb_ucfirst($str, $encoding = 'UTF-8')
    {
        return mb_strtoupper( mb_substr($str, 0, 1, $encoding), $encoding)
        . mb_substr($str, 1, mb_strlen($str), $encoding);
    }

    static public function mb_lcfirst($str, $encoding = 'UTF-8')
    {
        return mb_strtolower( mb_substr($str, 0, 1, $encoding), $encoding)
        . mb_substr($str, 1, mb_strlen($str), $encoding);
    }

    static public function isUserAdmin()
    {
		return Yii::$app->user->identity && Yii::$app->user->identity->isAdmin;
	}

	static public function yiiparam($name, $default = null)
	{
		if ( isset(Yii::$app->params[$name]) )
			return Yii::$app->params[$name];
		else
			return $default;
	}

}
