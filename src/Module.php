<?php
namespace santilin\churros;

use Yii;

class Module extends \yii\base\Module
{
	public $controllerNamespace = 'santilin\churros\controllers';

	public function init()
	{
		parent::init();
		if (Yii::$app instanceof \yii\console\Application) {
			$this->controllerNamespace = 'santilin\churros\console\controllers';
		}
	}

} // class Module
