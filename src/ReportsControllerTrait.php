<?php
namespace santilin\churros;

use Yii;
use yii\base\ViewNotFoundException;
use yii\web\Response;

trait ReportsControllerTrait
{
	/**
	 * Displays index page.
	 *
	 * @return string
	 */
	public function actionIndex()
	{
		$params = [];
		$ret = $this->render('index', $params);
		return $ret;
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
				$report->t('churros', "Informe <strong>{record}</strong>: no está definido el modelo $search_model_name"));
			return $this->redirect(['view', 'id'=>$id]);
		}
		$search_model = new $search_model_name;
		$params = Yii::$app->request->post();
		$report->decodeValue();
		$report->load($params);
		$report->encodeValue();
		if( $this->doSave($report) ) {
			Yii::$app->session->setFlash('success',
				$report->t('churros', "El informe <strong>{record}</strong> se ha guardado correctamente."));
		}
		try {
			return $this->render('report', [
				'report' => $report,
				'reportModel' => $search_model,
				'params' => $params,
				'extraParams' => $this->changeParams($params, 'report', $report)
			]);
		} catch( \yii\base\InvalidArgumentException $e ) {
			Yii::$app->session->setFlash('error',
				$report->t('churros', "El informe <strong>{record}</strong> tiene errores en su definición"));
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
				$report->t('churros', "Informe <strong>{record}</strong>: no está definido el modelo '$search_model_name'"));
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
				'extraParams' => $this->changeParams($params, 'report', $report)
			]);
		} catch( \yii\base\InvalidArgumentException $e ) {
			Yii::$app->session->setFlash('error',
				$report->t('churros', "El informe <strong>{record}</strong> tiene errores en su definición"));
			throw $e;
		}
	}



} // class SiteController

