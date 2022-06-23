<?php

namespace santilin\churros\validators;

use Yii;
use yii\validators\Validator;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use santilin\churros\assets\ChurrosAsset;

/**
 * RangeValidator validates that the attribute value is among a list of values.
 *
 * The range can be specified via the [[range]] property.
 * If the [[not]] property is set true, the validator will ensure the attribute value
 * is NOT among the specified range.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DotDotValidator extends Validator
{
    public $mask = false;
    public $dot = '.';


    public function init()
    {
        parent::init();
        if ($this->message === null) {
             $this->message = Yii::t('yii', "{attribute} doesn\t conform to the mask {$this->mask}.");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
    }

    public function clientValidateAttribute($model, $attribute, $view)
    {
        $options = $this->getClientOptions($model, $attribute);

		ChurrosAsset::register($view);
        return "value = churros_validate_dot_dot_input(\$form, attribute, messages, '{$this->mask}', '{$this->dot}', "
			. json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');';
    }

    /**
     * {@inheritdoc}
     */
    public function getClientOptions($model, $attribute)
    {
        $options = [
            'dot' => $this->dot,
            'message' => $this->formatMessage($this->message, [
                'attribute' => $model->getAttributeLabel($attribute),
            ]),
        ];
        if ($this->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }
		$options['allowArray'] = 1;

        return $options;
    }

	protected function maskToGroups()
	{
		$parts = explode($this->dot, $this->mask);
		$ret = [];
		foreach( $parts as $part) {
			$ret[] = strlen($part);
		}
		return $ret;
	}
    
    
}
