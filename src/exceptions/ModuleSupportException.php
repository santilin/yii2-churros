<?php

namespace santilin\churros\exceptions;
use Yii;
use santilin\churros\helpers\AppHelper;

class ModuleSupportException extends ModuleException
{
	public function __construct($message)
	{
		parent::__construct($message . "\n\n" .Yii::t('churros',
			'Si necesitas información sobre este error, por favor, envía un correo a {soporte} describiendo cómo has llegado aquí de forma que podamos reproducirlo.',
			[ 'soporte' => AppHelper::yiiparam('supportEmail', AppHelper::yiiparam('adminEmail'))]
		));
	}
}

