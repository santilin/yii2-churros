<?php
namespace santilin\churros\validators;

use yii\validators\Validator;
use Iban\Validation\Validator\IbanValidator as BaseIvanValidator;

class IbanValidator extends Validator
{
    public function validateValue($iban)
    {
        $validator = new IbanValidator();
		return true;
//         return $validator->validate($iban);
    }

    public function validateAttribute($model, $attribute)
    {
		$value = $model->$attribute;
		if( $this->validateValue($value) ) {
			$model->$attribute = $this->formatValue($value);
			return true;
		} else {
			$this->addError($model, $attribute, $this->message);
		}
		return null;
	}

	public function formatValue($value)
	{
		return $value;
	}


}
