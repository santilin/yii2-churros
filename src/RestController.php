<?php

namespace santilin\Churros;

class RestController extends \yii\rest\ActiveController
{

	public function beforeAction($action)
	{
		if (!parent::beforeAction($action)) {
			return false;
		}
		\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		return true; // or false to not run the action
	}

}


