<?php
namespace santilin\Churros\components;

use yii\validators\Validator;

class NifValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
		$validating_nif = new Nif($model->$attribute);
		if( !$validating_nif->verify() ) {
			if( $model instanceof ModelInfoTrait ) {
				$this->addError($model, $attribute,
					$model->t('app', "The {fldtitle} '{fldvalue}' is not valid", [
						'fldtitle' => $model->getAttributeLabel($attribute),
						'fldvalue' => $model->$attribute
					]));
			} else {
				$this->addError($model, $attribute, \Yii::t('app', "The $attribute '{$model->$attribute}' is not valid"));
			}
        }
    }
}
