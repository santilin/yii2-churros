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


	static public function lastWord($sentence, $sep = ' ')
	{
		$last_word_start = strrpos($sentence, $sep) + 1; // +1 so we don't include the space in our result
		$last_word = substr($sentence, $last_word_start);
		return $last_word;
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

    static public function userIsAdmin()
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


	/**
	* @param array      $array
	* @param int|string $position
	* @param mixed      $insert
	*/
	function array_insert(&$array, $position, $insert)
	{
		if (is_int($position)) {
			array_splice($array, $position, 0, $insert);
		} else {
			$pos   = array_search($position, array_keys($array));
			$array = array_merge(
				array_slice($array, 0, $pos),
				$insert,
				array_slice($array, $pos)
			);
		}
	}

    public static function mergeAndConcat(array $keys_to_concat, $a, $b)
    {
        $args = func_get_args();
        array_shift($args);
        $res = array_shift($args);
        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if ($v instanceof UnsetArrayValue) {
                    unset($res[$k]);
                } elseif ($v instanceof ReplaceArrayValue) {
                    $res[$k] = $v->value;
                } elseif (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::merge($res[$k], $v);
                } else {
					if( in_array($k, $keys_to_concat) ) {
						$res[$k] .= " $v";
					} else {
						$res[$k] = $v;
					}
                }
            }
        }
        return $res;
    }


}
