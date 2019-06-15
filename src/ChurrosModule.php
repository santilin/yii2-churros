<?php
namespace santilin\Churros;

use Yii;

class ChurrosModule extends \yii\base\Module
{
	public $controllerNamespace = 'santilin\Churros\controllers';

	public function init()
	{
		parent::init();
		if (Yii::$app instanceof \yii\console\Application) {
			$this->controllerNamespace = 'santilin\Churros\console\controllers';
		}
	}
}
