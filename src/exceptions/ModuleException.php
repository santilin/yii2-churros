<?php

namespace santilin\churros\exceptions;
use Yii;

class ModuleException extends \yii\base\UserException
{
	protected $module;

	public function __construct($message)
	{
		$this->module = Yii::$app->controller->module->id;
		parent::__construct($message);
	}
}

