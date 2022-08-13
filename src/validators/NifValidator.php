<?php
namespace santilin\churros\validators;

use yii\validators\Validator;

class NifValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
		$validating_nif = new Nif($model->$attribute);
		if( !$validating_nif->verify() ) {
			if( $model instanceof ModelInfoTrait ) {
				$this->addError($model, $attribute,
					$model->t('churros', "The value '{value}' is not valid for {attribute}", [
						'attribute' => $model->getAttributeLabel($attribute),
						'value' => $model->$attribute
					]));
			} else {
				$this->addError($model, $attribute,
					\Yii::t('churros', "The value '{value}' is not valid for {attribute}", [
						'attribute' => $attribute ,
						'value' => $model->$attribute,
					]));
			}
			return false;
        } else {
			return null;
		}
    }
}
