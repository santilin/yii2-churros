<?php

namespace santilin\churros;

use Yii;
use yii\base\ViewNotFoundException;
use yii\web\Response;

use santilin\churros\helpers\AppHelper;
use santilin\churros\grid\ReportView;

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
		$model->setDefaultValues();
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
			if( isset($params['pdf']) ) {
				$content = $this->renderPartial('report', [
					'report' => $report,
					'reportModel' => $search_model,
					'params' => $params,
					'extraParams' => $this->changeActionParams($params, 'report', $report)
				]);
				$this->sendPdf($report, $content);
				die;
			} else {
				return $this->render('report', [
					'report' => $report,
					'reportModel' => $search_model,
					'params' => $params,
					'extraParams' => $this->changeActionParams($params, 'report', $report)
				]);
			}
		} catch( \yii\base\InvalidArgumentException $e ) {
			Yii::$app->session->setFlash('error',
				$report->t('churros', 'The report "{record}" has definition errors'));
			throw $e;
		}
	}


	protected function sendPdf($report, $content)
	{
		$pdfHeader=<<<EOF
<table width="100%" height="48px" style="vertical-align: bottom; font-family: serif;
	font-size: 8pt; color: #000000; font-weight: bold; font-style: italic;">
	<tr>
		<td width="20%"><img height='48px' src='/img/logo_icono.jpg'/></td>
		<td width="" style="text-align: center;">{$report->getReportTitle()}</td>
		<td width="20%" align="right">{DATE j-m-Y} - {PAGENO}/{nbpg}</td>
	</tr>
</table>
EOF;
		$pdfFooter = null;
		if( YII_DEBUG ) {
            Yii::$app->getModule('debug')->instance->allowedIPs = [];
        }
		$methods = [];
		$margin_top = AppHelper::yiiparam('pdfMarginTop', 30); // Inicio del contenido de la página sin header desde el borde superior de la página
		$margin_bottom = AppHelper::yiiparam('pdfMarginBottom', 20);
		$margin_header = AppHelper::yiiparam('pdfMarginHeader', 10); // Inicio del header desde el borde superior de la página
		$margin_footer = AppHelper::yiiparam('pdfMarginFooter', 15);
		if( $this->findViewFile('_report_header') ) {
			$header_content = $this->renderPartial('_report_header', ['model'=>$report]);
			// h:{00232}
			if( strncmp($header_content,'h:{',3) === 0 ) {
				$margin_top = intval(substr($header_content,3,5));
				$header_content = substr($header_content,9);
			}
			$methods['setHeader'] = $header_content;
		} else {
// 			$methods['setHeader'] = date('Y-m-d H:i') . '|'
// 				. $model->getModelInfo('title') . '|' . Yii::$app->name . ' - {PAGENO}';
			$methods['setHeader'] = $pdfHeader;
 			$margin_top = 30;
 			$margin_header = 10;
		}
		if( $this->findViewFile('_pdf_footer') ) {
			$methods['setFooter'] = $this->renderPartial('_pdf_footer', ['model'=>$model]);
		} else {
			$methods['setFooter'] = $pdfFooter;
		}
		$pdf = new \kartik\mpdf\Pdf([
			'mode' => \kartik\mpdf\Pdf::MODE_CORE,
			'format' => \kartik\mpdf\Pdf::FORMAT_A4,
			'orientation' => \kartik\mpdf\Pdf::ORIENT_PORTRAIT,
			'destination' => \kartik\mpdf\Pdf::DEST_DOWNLOAD,
			'marginHeader' => $margin_header, // Margin from top of page
			'marginFooter' => $margin_footer, // Margin from bottom of page
			'marginTop' => $margin_top, // Margin from top of page to content
			'marginBottom' => $margin_bottom, // $margin_footer,
			'content' => $content,
// 			'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
			'cssInline' => file_get_contents(Yii::getAlias('@app') . '/web/css/print.css'),
			'options' => ['title' => $report->recordDesc()],
			'methods' => $methods,
		]);
 		$pdf->filename = "/home/santilin/devel/tmp/pdf.pdf";
		return $pdf->render();
	}

} // class SiteController

