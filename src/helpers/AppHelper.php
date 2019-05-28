<?php
/**
 * @link
 * @copyright
 * @license
 */

namespace santilin\Churros\helpers;

use Yii;

class AppHelper
{

    static public function empty($value)
    {
		return empty($value);
	}

	static public function getAppLocaleLanguage()
	{
		return str_replace("-", "_", Yii::$app->language);
	}

	static public function joinModels($glue, $parentmodel, $models, $attribute = null)
	{
		if( $models == null || count($models)==0 ) {
			return "";
		}
		$attrs = [];
		if ($attribute == null ) {
			$attribute = $models[0]->getModelInfo('code_field');
// 			die(print_r($models[0],true));
		}
		foreach((array)$models as $model) {
			if( $model != null ) {
				$url = "/" . $model->controllerName() . "/" . strval($model->getPrimaryKey()) . "?parent_controller=". $parentmodel->controllerName() . "&parent_id=" . strval($parentmodel->getPrimaryKey());
				$attrs[] = "<a href='$url'>" .  $model->$attribute . "</a>";
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
		$str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
		$str = trim($str);
		// uppercase the first character of each word
		$str = ucwords($str);
		$str = str_replace(" ", "", $str);
		$str = lcfirst($str);

		return $str;
	}

}
