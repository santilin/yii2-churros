<?php

namespace santilin\churros\validators;

use Yii;
use yii\validators\Validator;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * FormatValidator formats a value prior to save.
 *
 * @author Santilín <z@zzzzz.es>
 */
class FormatValidator extends Validator
{
	public $format;

    public function validateAttribute($model, $attribute)
    {
		$value = $model->{$attribute};
		if( ($message = $this->validateValue($value)) === null ) {
			$model->{$attribute} = $this->formatValue($this->format, $value);
		} else {
			$this->addError($model, $attribute, $message);
		}
	}

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
		return null;
	}

	protected function formatValue($format, $value)
	{
		for( $i=0; $i<strlen($format); ++$i) {
			switch($format[$i]) {
			case '<':
				$value = mb_strtoupper($value);
				break;
			case '>':
				$value = mb_strtolower($value);
				break;
			default:
				break;
			}
		}
		return $value;
	}

}
