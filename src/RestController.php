<?php namespace santilin\Churros;

use Yii;

class RestController extends \yii\rest\ActiveController
{

    public function actions()
    {
		$actions = parent::actions();
		$actions['view'] = [
			'class' => 'santilin\Churros\RestViewAction',
			'modelClass' => $this->modelClass,
			'checkAccess' => [$this, 'checkAccess'],
        ];
        return $actions;
    }
    
	protected function verbs()
	{
		return [
			'index' => ['GET', 'HEAD','OPTIONS'], //instead of  'index' => ['GET', 'HEAD']
			'view' => ['GET', 'HEAD','OPTIONS'],
			'create' => ['POST','OPTIONS'],
			'update' => ['PUT', 'PATCH', 'OPTIONS'],
			'delete' => ['DELETE'],
		];
	}

	public function beforeAction($action)
	{
		if (!parent::beforeAction($action)) {
			return false;
		}
		if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {
			// https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Methods
            \Yii::$app->getResponse()->getHeaders()->set('Access-Control-Allow-Methods', 'POST,GET,PUT,PATCH,HEAD');
            \Yii::$app->getResponse()->getHeaders()->set('Access-Control-Allow-Headers', 'Content-type');
            \Yii::$app->end();
        }
		\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		return true; // or false to not run the action
	}
	
}


