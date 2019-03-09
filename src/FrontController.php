<?php

namespace santilin\Churros;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UnauthorizedHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

class FrontController extends CrudController
{
	public $layout = 'main';

    public function behaviors() {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'logout' => ['post'],
                ],
            ],
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@']
                    ],
                    [
                        'allow' => false,
                    ]
                ]
            ]
        ];
    }

    public function checkUserHasAccess($user_id, $action = null)
    {
		$user = Yii::$app->user;
		if( $user && $user_id == $user->id) {
			return true;
		}
		return false;
	}

    public function failIfUserNotAllowed($user_id, $message = null, $action = null )
    {
		if( $message == null ) {
			$message = User::t('app', "{La} {title_singular} no tiene acceso"
				. ($action == null ? '': " a la acción '$action'" ));
		}
		if( !$this->checkUserHasAccess($user_id, $action) ) {
			throw UnauthorizedHttpException($message);
		}
	}


}
