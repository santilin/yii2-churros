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
	 * Lists all models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		$params = Yii::$app->request->queryParams;
		$searchModel = $this->createSearchModel();
		$params['permissions'] = ($params['permissions']??true===false) ? false : $this->crudActions;
		$params = $this->changeActionParams($params, 'index', $searchModel);
		return $this->render('index', [
			'searchModel' => $searchModel,
			'indexParams' => $params,
			'indexGrids' => [ '_report_grid' => [ '', null, [] ] ]
		]);
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreate()
	{
		$params = Yii::$app->request->queryParams;
		$model_name = '\\app\\forms\\' . $this->_model_name . '_report_form_Form';
		$report_def = $this->findFormModel(null, $model_name, 'create', $params);
		if (isset($_POST['_form_relations']) ) {
			$relations = explode(",", $_POST['_form_relations']);
		} else {
			$relations = [];
		}
		if ($report_def->loadAll(Yii::$app->request->post(), $relations) ) {
			if( $report_def->saveAll(true) ) {
				$this->addSuccessFlashes('create', $report_def);
				return $this->whereToGoNow('create', $report_def);
			}
		}
		return $this->render('create', [
			'model' => $report_def,
			'extraParams' => $this->changeActionParams($params, 'create', $report_def)
		]);
	}

	/**
	 * @param integer $id The id of the report to update
	 */
	public function actionUpdate($id)
	{
		$params = Yii::$app->request->post();
		$model_name = '\\app\\forms\\' . $this->_model_name . '_report_form_Form';
		$report_def = $this->findFormModel($id, $model_name, 'update', $params);
		if( !$report_def->checkAccessByRole('roles') ) {
			Yii::$app->session->setFlash('error',
				$report_def->t('churros', "{model.title}: you have not access to this report"));
			return $this->redirect(['index']);
		}
		$report_model_name = $report_def->model;
		if( strpos($report_model_name, '\\') === FALSE ) {
			$report_model_name= "\\app\\models\\$report_model_name";
		}
// 		if( substr($search_model_name, -7) != "_Search" ) {
// 			$search_model_name .= "_Search";
// 		}
		if( $report_def->model == '' || !class_exists($report_model_name) ) {
			Yii::$app->session->setFlash('error',
				$report_def->t('churros', '{search_model_name}: search model not found in report "{record}"', ['search_model_name' => $search_model_name]));
			return $this->redirect(['view', 'id'=>$id]);
		}
		$report_def->decodeValue();
		if (!$report_def->load($params)) {
			throw new \Exception("Error al cargar los datos del formulario via POST");
		}
		$report_def->encodeValue();
		if( $report_def->save() ) {
			$this->addSuccessFlashes('update', $report_def);
		}
		try {
			return $this->render('report', [
				'reportDef' => $report_def,
				'reportModel' => new $report_model_name,
				'params' => $params,
				'extraParams' => $this->changeActionParams($params, 'report', $report_def)
			]);
		} catch( \yii\base\InvalidArgumentException $e ) {
			Yii::$app->session->setFlash('error',
				$report_def->t('churros', 'The report "{record}" has definition errors'));
			throw $e;
		}
	}

	/**
	 * @param integer $id The id of the report to show
	 */
	public function actionView($id)
	{
		// Columns, filters, etc from the GUI report builder
		$req = Yii::$app->request;
		$params = $req->isPost ? $req->post():$req->get();
		if( isset($params['save']) ) {
			return $this->actionUpdate($id);
		}
		$model_name = '\\app\\forms\\' . $this->_model_name . '_report_form_Form';
		$report_def = $this->findFormModel($id, $model_name, 'update', $params);
		if( !$report_def->checkAccessByRole('roles') ) {
			Yii::$app->session->setFlash('error',
				$report_def->t('churros', "{model.title}: you have not access to this report"));
			return $this->redirect(['index']);
		}
		$report_model_name = $report_def->model;
		if( strpos($report_model_name, '\\') === FALSE ) {
			$report_model_name= "\\app\\models\\$report_model_name";
		}
// 		if( substr($search_model_name, -7) != "_Search" ) {
// 			$search_model_name .= "_Search";
// 		}
		if( $report_def->model == '' || !class_exists($report_model_name) ) {
			Yii::$app->session->setFlash('error',
				$report_def->t('churros', '{search_model_name}: search model not found in report "{record}"', ['search_model_name' => $search_model_name]));
			return $this->redirect(['index']);
		}
		// The columns, filters, etc, from the saved definition
		$report_def->decodeValue();
		// Merge the post params and replace the saved ones
		$report_def->load($params);
		try {
			if( isset($params['pdf']) ) {
				$content = $this->renderPartial('report', [
					'reportDef' => $report_def,
					'reportModel' => new $report_model_name,
					'params' => $params,
					'extraParams' => $this->changeActionParams($params, 'report', $report_def)
				]);
				$this->sendPdf($report_def, $content, $this->pdf_css);
				die;
			} else {
				return $this->render('report', [
					'reportDef' => $report_def,
					'reportModel' => new $report_model_name,
					'params' => $params,
					'extraParams' => $this->changeActionParams($params, 'report', $report_def)
				]);
			}
		} catch( \yii\base\InvalidArgumentException $e ) {
			Yii::$app->session->setFlash('error',
				$report_def->t('churros', 'The report "{record}" has definition errors'));
			throw $e;
		}
	}

	protected function sendPdf($report_def, string $content, array $css)
	{
		$pdfHeader=<<<EOF
<table width="100%" height="48px" style="vertical-align: bottom; font-family: serif;
	font-size: 8pt; color: #000000; font-weight: bold; font-style: italic;">
	<tr>
		<td width="20%"><img height='48px' src='/img/logo_icono.jpg'/></td>
		<td span style="font-size:x-large;text-align: right;">{$report_def->getReportTitle()}</span><br/>
		{DATE d-m-Y} - {PAGENO}/{nbpg}</td>
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
			$header_content = $this->renderPartial('_report_header', ['model'=>$report_def]);
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
		$css[] = '/web/css/print.css';
		$inline_css = '';
		foreach ($css as $css_file) {
			$inline_css .= file_get_contents(Yii::getAlias('@app') . $css_file);
		}
		$pdf = new \kartik\mpdf\Pdf([
			'mode' => \kartik\mpdf\Pdf::MODE_CORE,
			'format' => \kartik\mpdf\Pdf::FORMAT_A4,
			'orientation' => $report_def->landscape
				? \kartik\mpdf\Pdf::ORIENT_LANDSCAPE : \kartik\mpdf\Pdf::ORIENT_PORTRAIT,
			'destination' => \kartik\mpdf\Pdf::DEST_STRING,
			'marginHeader' => $margin_header, // Margin from top of page
			'marginFooter' => $margin_footer, // Margin from bottom of page
			'marginTop' => $margin_top, // Margin from top of page to content
			'marginBottom' => $margin_bottom, // $margin_footer,
			'content' => $content,
// 			'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
			'cssInline' => $inline_css,
			'options' => ['title' => $report_def->recordDesc()],
			'methods' => $methods,
		]);
		$s = $pdf->render();
		header('Cache-Control: public');
		header('Content-Type: application/pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.strlen($s));
		header('Content-Disposition: attachment; filename=' . $report_def->getReportTitle() . '.pdf');
		echo $s;
	}

} // class SiteController

