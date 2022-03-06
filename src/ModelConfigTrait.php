<?php
namespace santilin\churros;

trait ModelConfigTrait
{
	static public function getValue($var, $default = "")
	{
		$config = static::find()->where(['name' => $var])->one();
		if( $config ) {
			return $config->value;
		} else {
			return $default;
		}
	}

	static public function getValueClean($var, $default = "")
	{
		$value = static::getValue($var, $default);
		$value = str_replace("<p>", "", $value);
		$value = str_replace("</p>", "", $value);
		return $value;
	}
}
