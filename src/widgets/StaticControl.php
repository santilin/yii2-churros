<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;

class StaticControl extends \yii\widgets\InputWidget
{
	public $value = null;
	public $readonly = null;
	public $tabindex = null;

	public function run()
	{
		if ($this->value !== null) {
			$value = $this->value;
		} else {
			if (isset($this->options['value'])) {
				$value = $this->options['value'];
				if (is_callable($value)) {
					$value = call_user_func($value);
				}
				unset($this->options['value']);
			} else {
				$value = Html::getAttributeValue($this->model, $this->field->attribute);
			}
		}
        Html::addCssClass($this->options, 'form-control-plaintext');
		if (is_callable($value)) {
			$value = call_user_func($value);
		}
        $value = (string)$value;
		return Html::tag('div', $value, $this->options);
	}
}
