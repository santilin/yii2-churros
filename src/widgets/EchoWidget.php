<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\ArrayHelper;

class EchoWidget extends \yii\widgets\InputWidget
{
	public function run()
	{
		$classes = explode(' ', $this->options['class']);
// 		foreach ($classes as $key => $class) {
// 			if ($class=='form-control') {
// 				$classes[$key] = 'form-control-plaintext';
// 			}
// 		}
// 		if (empty($classes)) {
// 			$classes[] = 'form-control-plaintext';
// 		}
		$s_classes = implode(' ', $classes);
		return "<div class=\"$s_classes\">{$this->value}</div>";
	}
}
