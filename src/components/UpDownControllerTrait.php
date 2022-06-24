<?php

namespace santilin\churros\components;

use Yii;

class UpDownControllerTrait
{
	public function actionUp($id)
	{
		$params = Yii::$app->request->queryParams;
		$model = $this->findModel($id);
		$searchModel = $this->createSearchModel();
		if( isset($params['order_field']) ) {
			$order_field = $params['order_field'];
			$save_order = $model->$order_field;
			$dp = $searchModel->search($params);
			$query = $dp->query;
			$query->select($order_field);
			$query->andWhere(['<', $order_field, $save_order]);
			$query->orderBy("$order_field DESC");
			$query->limit(1);
			$query->asArray();
			$prev = $query->scalar();
			$model->$order_field = $prev - 1;
			if( $model->$order_field < 0 ) {
				$model->$order_field = 0;
			}
			if( $model->$order_field != $save_order ) {
				if( $model->save() ) {
					Yii::$app->session->addFlash('success', "Se ha guardado con el número {$model->$order_field}");
				} else {
					Yii::$app->session->addFlash('error', "No se ha guardado: {$model->getOneError()}");
				}
			}
		}
		if( isset($params['returnTo']) ) {
			return $this->redirect($params['returnTo']);
		}
		$fname = $searchModel->formName();
		unset($params['_pjax']);
		return $this->redirect(Url::to(['index',
			$fname => $params[$fname],
			'sort' => $params['sort']??''] ));
	}


	public function actionDown($id)
	{
		$params = Yii::$app->request->queryParams;
		$model = $this->findModel($id);
		$searchModel = $this->createSearchModel();
		if( isset($params['order_field']) ) {
			$order_field = $params['order_field'];
			$save_order = $model->$order_field;
			$dp = $searchModel->search($params);
			$query = $dp->query;
			$query->select($order_field);
			$query->andWhere(['>', $order_field, $save_order]);
			$query->orderBy("$order_field ASC");
			$query->groupby($order_field);
			$query->limit(1);
			$query->asArray();
			$next = $query->scalar();
			if( $next != 0 ) {
				$model->$order_field = $next + 1;
			}
			if( $model->$order_field != $save_order ) {
				if( $model->save() ) {
					Yii::$app->session->addFlash('success', "Se ha guardado con el número {$model->$order_field}");
				} else {
					Yii::$app->session->addFlash('error', "No se ha guardado: {$model->getOneError()}");
				}
			}
		}
		if( isset($params['returnTo']) ) {
			return $this->redirect($params['returnTo']);
		}
		$fname = $searchModel->formName();
		unset($params['_pjax']);
		return $this->redirect(Url::to(['index',
			$fname => $params[$fname],
			'sort' => $params['sort']??''] ));
	}

	public function actionSparse($start, $inc)
	{
		$params = Yii::$app->request->queryParams;
		$searchModel = $this->createSearchModel();
		if( isset($params['order_field']) ) {
			$order_field = $params['order_field'];
			$dp = $searchModel->search($params);
			$dp->query->orderBy($order_field);
			$models = $dp->getModels();
			foreach( $models as $model ) {
				$save_order = $model->$order_field;
				if( !$model->oculto() ) {
					if( $model->$order_field != ($start==0?1:$start) ) {
						$model->$order_field = ($start==0?1:$start);
					}
					$start += $inc;
				} else {
					$model->$order_field = 0;
				}
				if( $save_order != $model->$order_field ) {
					if( !$model->save() ) {
						Yii::$app->session->addFlash('error', "No se ha guardado: {$model->getOneError()}");
						break;
					}
				}
			}
			Yii::$app->session->addFlash('success', "Se han espaciado " . count($models));
		}
		if( isset($params['returnTo']) ) {
			return $this->redirect($params['returnTo']);
		}
	}

}
