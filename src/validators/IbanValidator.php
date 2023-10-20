<?php
namespace santilin\churros\validators;

use yii\validators\Validator;
use PHP_IBAN\IBAN;

class IbanValidator extends Validator
{
    public function validateValue($iban)
    {
		$iv = new IBAN($iban);
		return $iv->verify();
    }

    public function validateAttribute($model, $attribute)
    {
		$value = $model->$attribute;
		$iv = new IBAN($iban);
		if ($iv->verify()) {
			$model->$attribute = $iv->MachineFormat();
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
