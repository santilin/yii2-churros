<?php
namespace santilin\churros\validators;

use yii\validators\NumberValidator;

use Yii;
use yii\helpers\StringHelper;
use yii\web\JsExpression;

class SpanishNumberValidator extends NumberValidator
{
    /**
     * @var string the regular expression for matching numbers. It defaults to a pattern
     * that matches floating numbers with optional exponential part (e.g. -1.23e-10).
     */
    public $clientNumberPattern = '/^\s*[-+]?[0-9]*[\.|,]?[0-9]+([eE][-+]?[0-9]+)?\s*$/';

    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
		if (strpos($value, ',') !== FALSE) {
			$model->$attribute = str_replace(',', '.', $value);
		}
		return parent::validateAttribute($model, $attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function getClientOptions($model, $attribute)
    {
		$options = parent::getClientOptions($model, $attribute);
		if (!$this->integerOnly) {
			$options['pattern'] = new JsExpression($this->clientNumberPattern);
		}
        return $options;
    }

}
