<?php

namespace santilin\churros\components;

use Yii;

class EchoWidget extends \yii\widgets\InputWidget
{
	public $content = '';
	public function run()
	{
		return $this->content;
	}
}
