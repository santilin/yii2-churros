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
	 * @param integer $id The id of the report to show
	 */
	public function actionView($id)
	{
		$report = $this->findModel($id);
		$search_model_name = $report->model;
		if( strpos($search_model_name, '\\') === FALSE ) {
			$search_model_name= 'app\models\search\\' . $search_model_name;
		}
		if( substr($search_model_name, -6) != "Search" ) {
			$search_model_name .= "Search";
		}
		if( $report->model == '' || !class_exists($search_model_name) ) {
			Yii::$app->session->setFlash('error',
				$report->t('churros', "Informe <strong>{record}</strong>: no está definido el modelo"));
			return $this->redirect(['view', 'id'=>$id]);
		}
		$search_model = new $search_model_name;
		$params = Yii::$app->request->post();
		$report->decodeValue();
		$report->load($params);
		if( isset($params['save']) ) {
			$report->encodeValue();
			if( $this->doSave($report) ) {
				Yii::$app->session->setFlash('success',
					$report->t('churros', "El informe <strong>{record}</strong> se ha guardado correctamente."));
			}
		}
		try {
			return $this->render('report', [
				'report' => $report,
				'reportModel' => $search_model,
				'params' => $params,
				'extraParams' => $this->extraParams('report', $report)
			]);
		} catch( \yii\base\InvalidArgumentException $e ) {
			Yii::$app->session->setFlash('error',
				$report->t('churros', "El informe <strong>{record}</strong> tiene errores en su definición"));
			throw $e;
// 			return $this->redirect(['view', 'id'=>$id]);
		}
	}



} // class SiteController

