<?php

namespace santilin\Churros\components;

use yii\helpers\Html;

/* https://www.yiiframework.com/doc/api/2.0/yii-i18n-formatter */
class Formatter extends \yii\i18n\Formatter
{
	public function asTruncatedText($text)
	{
		return trim(substr($text, 0, 100)) . "&hellip;";
	}

	public function asTelephone($text)
	{
		if( trim($text) != '') {
			return  Html::tag('span', '&nbsp;', ['class' => 'glyphicon glyphicon-phone-alt'])
				. Html::a($text, "tel://$text");
		} else {
			return '';
		}
	}

	public function asPercent100($value, $decimals = null, $options = [], $textOptions = [])
	{
		return parent::asPercent($value / 100, 2, $options, $textOptions);
	}

}
