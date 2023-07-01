<?php

namespace santilin\churros\components;

use Yii;
use yii\helpers\{Html,Url};
use santilin\churros\components\Taxonomy;

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
		if( $minutes == '' ) {
			$minutes = 0;
		}
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

	public function asUploadedImage($images, $options = [])
	{
		if( !is_array($images) ) {
			$tmp = @unserialize($images);
			if( $tmp != null ) {
				$images = $tmp;
			}
		}
		if( empty($images) ) {
			return '';
		}
		if( !is_array($images) ) {
			$images = [$images];
		}
		$ret = '';
		foreach( $images as $image ) {
			$ret .= Html::a(self::asImage(Yii::getAlias("@uploads/$image"), $options),
			Url::to("@uploads/$image"));
		}
		return $ret;
	}

	public function asTokenized($value, $sep = ',')
	{
		if( is_string($value) ) {
			$l = strlen($value);
			if( $l > 2 ) {
				if( $value[$l-1] == $sep ) {
					if( $value[0] == $sep ) {
						return substr($value, 1, $l-2);
					}
				} else if( $value[0] == $sep ) {
					return substr($value,1);
				} else {
					return $value;
				}
			} else {
				return $value;
			}
		} else {
			throw new \Exception("Please, implement asTokenized for arrays");
		}
	}
	/**
	 * @todo move to app's components/Formatter
	 */
	public function asTaxonomy(?string $value, array $taxonomy, string $sep = '/'): string
	{
		if ($value) {
			return Taxonomy::format($value, $taxonomy, $sep);
		} else {
			return '';
		}
	}

	public function asLabel($value, $options): string
	{
		return $options[$value]??$value;
	}

}
