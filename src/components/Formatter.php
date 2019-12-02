<?php

namespace santilin\churros\components;

use yii\helpers\Html;

/* https://www.yiiframework.com/doc/api/2.0/yii-i18n-formatter */
class Formatter extends \yii\i18n\Formatter
{
	public function asTruncatedText($text)
	{
		return trim(substr($text, 0, 100)) . "&hellip;";
	}

	public function asPhoneNumber($text)
	{
		if( trim($text) != '') {
// 			return  Html::tag('span', '&nbsp;', ['class' => 'glyphicon glyphicon-phone-alt']) .
			return Html::a($text, "tel://$text");
		} else {
			return '';
		}
	}

	public function asPercent100($value, $decimals = null, $options = [], $textOptions = [])
	{
		return parent::asPercent($value / 100, 2, $options, $textOptions);
	}

	public function asBooleanInversed($value)
	{
		return $this->asBoolean(!(bool)$value);
	}

	public function asHours($minutes)
	{
		return number_format(($minutes / 60),2,',','');
	}


	public function asHoursMinutes($minutes)
	{
		if( empty($minutes) ) {
			return "00:00";
		} else {
			return str_pad(floor($minutes / 60), 2, "0", STR_PAD_LEFT)
				. ":" . substr("00" . ($minutes % 60), -2);
		}
	}

}
