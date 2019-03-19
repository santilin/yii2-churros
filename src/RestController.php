<?php namespace santilin\Churros;

use Yii;
use yii\web\Response;
use yii\helpers\ArrayHelper;
use yii\filters\ContentNegotiator;

class RestController extends \yii\rest\ActiveController
{
    public $serializer = 'tuyakhov\jsonapi\Serializer';

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'contentNegotiator' => [
                'class' => ContentNegotiator::className(),
                'formats' => [
                    'application/vnd.api+json' => Response::FORMAT_JSON,
                ],
            ]
        ]);
    }

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

	protected function verbs2()
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
		if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {
			// https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Methods
            Yii::$app->getResponse()->getHeaders()->set('Access-Control-Allow-Headers', 'Origin, Methods');
            Yii::$app->getResponse()->getHeaders()->set('Access-Control-Allow-Origin', '*');
            Yii::$app->getResponse()->getHeaders()->set('Access-Control-Allow-Methods', 'POST,GET,PUT,PATCH,HEAD,OPTIONS');
        }
		return parent::beforeAction($action);
	}

}


