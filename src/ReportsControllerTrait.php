<?php

namespace santilin\churros;

use Yii;
use yii\base\ViewNotFoundException;
use yii\web\Response;

trait ReportsControllerTrait
{

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreate()
	{
		$params = Yii::$app->request->queryParams;
		$model_name = '\\app\\models\\comp\\' . $this->_model_name . '_reportForm';
		$model = new $model_name;
		if (isset($_POST['_form_relations']) ) {
			$relations = explode(",", $_POST['_form_relations']);
		} else {
			$relations = [];
		}
		if ($model->loadAll(Yii::$app->request->post(), $relations) ) {
			if( $this->saveAll('create', $model) ) {
				if( $this->afterSave('create', $model) ) {
					$this->showFlash('create', $model);
					return $this->whereToGoNow('create', $model);
				}
			}
		}
		return $this->render('create', [
			'model' => $model,
			'extraParams' => $this->changeActionParams($params, 'create', $model)
		]);
	}

	/**
	 * @param integer $id The id of the report to update
	 */
	public function actionUpdate($id)
	{
		$report = $this->findModel($id);
		$search_model_name = $report->model;
		if( strpos($search_model_name, '\\') === FALSE ) {
			$search_model_name= "app\models\comp\\$search_model_name";
		}
		if( substr($search_model_name, -6) != "Search" ) {
			$search_model_name .= "Search";
		}
		if( $report->model == '' || !class_exists($search_model_name) ) {
			Yii::$app->session->setFlash('error',
				$report->t('churros', '{search_model_name}: model not found in report "{record}"', ['{search_model_name}' => $search_model_name]));
			return $this->redirect(['view', 'id'=>$id]);
		}
		$search_model = new $search_model_name;
		$params = Yii::$app->request->post();
		$report->decodeValue();
		$report->load($params);
		$report->encodeValue();
		if( $this->saveAll('update', $report) ) {
			if( $this->afterSave('update', $report) ) {
				$this->showFlash('update', $report);
			}
		}
		try {
			return $this->render('report', [
				'report' => $report,
				'reportModel' => $search_model,
				'params' => $params,
				'extraParams' => $this->changeActionParams($params, 'report', $report)
			]);
		} catch( \yii\base\InvalidArgumentException $e ) {
			Yii::$app->session->setFlash('error',
				$report->t('churros', 'The report "{record}" has definition errors'));
			throw $e;
		}
	}

	/**
	 * @param integer $id The id of the report to show
	 */
	public function actionView($id)
	{
		// Columns, filters, etc from the GUI report builder
		$params = Yii::$app->request->post();
		if( isset($params['save']) ) {
			return $this->actionUpdate($id);
		}
		$report = $this->findModel($id);

		$search_model_name = $report->model;
		if( strpos($search_model_name, '\\') === FALSE ) {
			$search_model_name= "app\models\comp\\$search_model_name";
		}
		if( substr($search_model_name, -6) != "Search" ) {
			$search_model_name .= "Search";
		}
		if( $report->model == '' || !class_exists($search_model_name) ) {
			Yii::$app->session->setFlash('error',
				$report->t('churros', '{search_model_name}: model not found in report "{record}"', ['{search_model_name}' => $search_model_name]));
			return $this->redirect(['view', 'id'=>$id]);
		}
		$search_model = new $search_model_name;
		// The columns, filters, etc, from the saved definition
		$report->decodeValue();
		// Merge the post params and replace the saved ones
		$report->load($params);
		try {
			return $this->render('report', [
				'report' => $report,
				'reportModel' => $search_model,
				'params' => $params,
				'extraParams' => $this->changeActionParams($params, 'report', $report)
			]);
		} catch( \yii\base\InvalidArgumentException $e ) {
			Yii::$app->session->setFlash('error',
				$report->t('churros', 'The report "{record}" has definition errors'));
			throw $e;
		}
	}



} // class SiteController

