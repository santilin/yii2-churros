<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\ArrayHelper;

class EchoWidget extends \yii\widgets\InputWidget
{
	public function run()
	{
		$classes = explode(' ', $this->options['class']);
		$s_classes = implode(' ', $classes);
		if (is_callable($this->value)) {
			return "<div class=\"$s_classes\">" . call_user_func($this->value) . "</div>";
		} else {
			return "<div class=\"$s_classes\">{$this->value}</div>";
		}
	}
}
