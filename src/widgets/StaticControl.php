<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;

class StaticControl extends \yii\widgets\InputWidget
{
	public $value = null;
	public string $nullText = '';

	public function run()
	{
		if ($this->value !== null) {
			$value = $this->value;
		} else if (array_key_exists('value', $this->options)) {
			$value = $this->options['value'];
			unset($this->options['value']);
		} else {
			$value = Html::getAttributeValue($this->model, $this->field->attribute);
		}
		if (is_callable($value)) {
			$value = call_user_func($value, $this->model, $this);
		}
        Html::addCssClass($this->options, 'fake-readonly-control');
		if (trim(strval($value)) == '') {
			$value = $this->nullText;
		}
		return Html::tag('div', $value, $this->options);
	}
}
