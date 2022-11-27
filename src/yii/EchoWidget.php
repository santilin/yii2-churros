<?php

namespace santilin\churros\yii;

use Yii;
use yii\helpers\ArrayHelper;

class EchoWidget extends \yii\widgets\InputWidget
{
	public function run()
	{
		$classes = ArrayHelper::remove($this->options, 'class', 'form-control readonly');
		return "<div class=\"$classes\">{$this->value}</div>";
	}
}
