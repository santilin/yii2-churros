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

	static public function joinModels($glue, $models, $attribute = null)
	{
		if( $models == null || count($models)==0 ) {
			return "";
		}
		$attrs = [];
		if ($attribute == null ) {
			$attribute = $models[0]->getModelInfo('code_field');
		}
		foreach((array)$models as $model) {
			if( $model != null ) {
				$attrs[] = "<a href='/" . lcfirst(self::stripNamespaceFromClassName($model)) . "/" . strval($model->getPrimaryKey()) . "'>" .  $model->$attribute . "</a>";
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

}
