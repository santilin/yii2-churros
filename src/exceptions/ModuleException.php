<?php

namespace santilin\churros\exceptions;
use Yii;

class ModuleException extends \yii\base\UserException
{
	protected $module;

	public function __construct($message)
	{
		$this->module = Yii::$app->module;
		parent::__construct($message);
	}
}

