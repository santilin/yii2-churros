<?php
namespace santilin\churros\validators;

use yii\validators\Validator;
use \Ramsey\Uuid\Uuid;

class UuidValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
		$uuid = $model->$attribute;
		$exact = $this->options['exact']??true;
		if( !static::validate($uuid, $exact)) ) {
			if( $model instanceof ModelInfoTrait ) {
				$this->addError($model, $attribute,
					$model->t('churros', "The UUID '{value}' is not valid for {attribute}", [
						'attribute' => $model->getAttributeLabel($attribute),
						'value' => $model->$attribute
					]));
			} else {
				$this->addError($model, $attribute,
					\Yii::t('churros', "The UUID '{value}' is not valid for {attribute}", [
						'attribute' => $attribute ,
						'value' => $model->$attribute,
					]));
			}
			return false;
        } else {
			return null;
		}
    }

    static public function validate(string &$uuid, bool $exact = true): bool
    {
		if( $exact ) {
			$uuid = Uuid::fromString($uuid);
		} else {
			$matches = [];
			$pat = substr(Uuid::VALID_PATTERN,1,-1);
			if( preg_match("/$pat/", $uuid, $matches) ) {
				$uuid = Uuid::fromString($matches[0]);
			}
		}
		if( $uuid instanceof Uuid ) {
			return true;
		}
		return false;
    }

}
