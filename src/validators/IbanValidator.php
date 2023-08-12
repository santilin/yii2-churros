<?php
namespace santilin\churros\validators;

use yii\validators\Validator;
use Iban\Validation\Validator\IbanValidator;

class IbanValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $iban = $model->$attribute;

        $validator = new IbanValidator();
        if (!$validator->validate($iban)) {
            $this->addError($model, $attribute, 'Invalid IBAN number.');
        }
    }
}
