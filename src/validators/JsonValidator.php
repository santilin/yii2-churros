<?php
namespace santilin\churros\validators;

use yii\validators\Validator;
use yii\helpers\Json;

class JsonValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        if (!is_string($value) || json_decode($value)===null) {
            $this->addError($model, $attribute, 'The field must contain a valid JSON string.');
        }
    }
}
