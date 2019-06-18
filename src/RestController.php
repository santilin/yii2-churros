<?php namespace santilin\churros;

use Yii;
use yii\filters\Cors;
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
            ],
            'corsFilter' => [
				'class' => Cors::className(),
				'cors' => [
					'Origin' => ['localhost'],
					'Access-Control-Allow-Credentials' => true,
					'Access-Control-Allow-Headers' => ['content-type'],
					'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
					'Access-Control-Expose-Headers' => ['Access_status_code'],
				]
			]
		]);
    }

    public function actions()
    {
		$actions = parent::actions();
		$actions['view'] = [
			'class' => 'santilin\churros\RestViewAction',
			'modelClass' => $this->modelClass,
			'checkAccess' => [$this, 'checkAccess'],
        ];
        return $actions;
    }

	protected function verbs()
	{
		return [
			'index' => ['GET', 'HEAD', 'OPTIONS'], //instead of  'index' => ['GET', 'HEAD']
			'view' => ['GET', 'HEAD', 'OPTIONS'],
			'create' => ['POST','OPTIONS'],
			'update' => ['PUT', 'PATCH', 'OPTIONS'],
			'delete' => ['DELETE'],
		];
	}

}


