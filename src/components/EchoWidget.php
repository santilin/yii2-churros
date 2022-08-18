<?php

namespace santilin\churros\components;

use Yii;
use yii\helpers\ArrayHelper;

class EchoWidget extends \yii\widgets\InputWidget
{
	public $content = '';
	public function run()
	{
		$classes = ArrayHelper::remove($this->options, 'class', 'form-control readonly');
		return "<div class=\"$classes\">{$this->content}</div>";
	}
}
