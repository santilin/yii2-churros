<?php
namespace santilin\churros\validators;

use yii;
use yii\helpers\Json;
use yii\validators\ValidationAsset;

class EmailValidator extends \yii\validators\EmailValidator
{
	public $default = null;
	public $skipOnEmpty = false;
	public $emptyMessage;
	public $required = false;


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->emptyMessage === null) {
            $this->emptyMessage = Yii::t('yii', '{attribute} cannot be blank.');
        }
    }

    public function validateAttribute($model, $attribute)
    {
        $value = strtolower(trim($model->$attribute));
        if( $value == '' ) {
			if( $this->required ) {
				$this->addError($model, $attribute, $this->emptyMessage);
			} else {
				$model->$attribute = $this->default;
			}
		} elseif( $this->validateValue($value) === null ) {
			$model->$attribute = $value;
		} else {
			$this->addError($model, $attribute, $this->message);
		}
    }

    public function clientValidateAttribute($model, $attribute, $view)
    {
        ValidationAsset::register($view);
        if ($this->enableIDN) {
            PunycodeAsset::register($view);
        }
        $options = $this->getClientOptions($model, $attribute);
        $ret = '';
		if( $this->required ) {
			$req_options = $options;
			$req_options['message'] = $this->emptyMessage;
			$ret = 'yii.validation.required(value, messages, ' . Json::htmlEncode($req_options) . ')&&';
		}
		$ret .= 'yii.churros.email(value, messages, ' . Json::htmlEncode($options) . ');';
		return $ret;
    }

} // class
