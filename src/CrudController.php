<?php

namespace santilin\churros;

use Yii;
use yii\helpers\Url;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\HttpException;
use yii\base\ErrorException;
use santilin\churros\exceptions\{DeleteModelException,SaveModelException};
use santilin\churros\helpers\AppHelper;

/**
 * CrudController implements the CRUD actions for yii2 models
 */
class CrudController extends \yii\web\Controller
{
	protected $allowedActions = [];
	public $accessOnlyOwner = false;
	const MSG_CREATED = '{La} {title} <a href="{model_link}">{record_medium}</a> has been successfully created.';
	const MSG_UPDATED = '{La} {title} <a href="{model_link}">{record_medium}</a> has been successfully updated.';
	const MSG_DELETED = '{La} {title} <strong>{record_long}</strong> has been successfully deleted.';
	const MSG_DUPLICATED = '{La} {title} <a href="{model_link}">{record_medium}</a> has been successfully duplicated.';
	const MSG_DEFAULT = 'The action on {la} {title} <a href="{model_link}">{record_medium}</a> has been successful.';

	/**
	 * An array of extra params to pass to the views
	 **/
	protected function changeActionParams(array $queryParams, string $action_id, $model)
	{
		return $queryParams;
	}

	public function behaviors()
	{
		$ret = [];
		$ret['verbs'] = [
			'class' => VerbFilter::className(),
			'actions' => [
				'delete' => ['post'],
				'logout' => ['post'],
			],
		];
		// Auth behaviors must be set on descendants of this controller
		return array_merge($ret, parent::behaviors());
	}

	public function beforeAction($action)
	{
        if( $this->request->getMethod() === 'POST' && count($_POST) == 0 && count($_FILES) == 0 ) {
            if( isset($_SERVER['CONTENT_TYPE']) && substr($_SERVER['CONTENT_TYPE'], 0, 19) == 'multipart/form-data' ) {
                if( isset($_SERVER['CONTENT_LENGTH']) ) {
                    if( intval($_SERVER['CONTENT_LENGTH'])>0 ) {
                        Yii::$app->session->addFlash('error', strtr(Yii::t('churros', 'PHP discarded POST data because of request exceeding either post_max_size={post_size} or upload_max_filesize={upload_size}'), ['{post_size}' => ini_get('post_max_size'), '{upload_size}' => ini_get('upload_max_filesize')]));
                        $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
                        return false;
                    }
                }
            }
        }
        if (!parent::beforeAction($action)) {
			return false;
		}
		if (isset($this->junctionModel) && $this->junctionModel == true ) {
			$id = $this->junctionIds();
			if( $id ) {
				// hack, take care
				$response = call_user_func_array([$this, $action->actionMethod], ['id' => $id]);
				if (!$response instanceof \yii\web\Response) {
					Yii::$app->response->data = $response;
				}
				return false;
			}
		}
		return true;
	}

	/**
	 * Lists all models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		$params = Yii::$app->request->queryParams;
		$searchModel = $this->findModel(null, null, 'search');
		if( $this->accessOnlyOwner ) {
			$params['accessOnlyOwner'] = $this->accessOnlyOwner;
		}
		$params = $this->changeActionParams($params, 'index', $searchModel);
		return $this->render('index', [
			'searchModel' => $searchModel,
			'indexParams' => $params,
		]);
	}

	/**
		* Displays a single model.
		* @param integer $id
		* @return mixed
		*/
	public function actionView($id)
	{
		$model = $this->findModel($id);
		$params = Yii::$app->request->queryParams;
		return $this->render('view', [
			'model' => $model,
			'extraParams' => $this->changeActionParams($params, 'view', $model)
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
		$model = $this->findModel(null);
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
		* Creates a new model by another data,
		* so user don't need to input all field from scratch.
		*
		* @param mixed $id
		* @return mixed
		*/
	public function actionDuplicate($id)
	{
		$params = Yii::$app->request->queryParams;
		if (Yii::$app->request->post('_asnew') != 0) {
			$id = Yii::$app->request->post('_asnew');
		}
		$model = $this->findModel($id);
		$model->setDefaultValues(true); // duplicating

		if (isset($_POST['_form_relations']) ) {
			$relations = explode(",", $_POST['_form_relations']);
		} else {
			$relations = [];
		}
		if ($model->loadAll(Yii::$app->request->post(), $relations) ) {
			$model->setIsNewRecord(true);
			foreach ($model->primaryKey() as $primary_key) {
				$model->$primary_key = null;
			}
			if( $this->saveAll('duplicate', $model) ) {
				if( $this->afterSave('duplicate', $model) ) {
					$this->showFlash('duplicate', $model);
					return $this->whereTogoNow('duplicate', $model);
				}
			}
		}
		return $this->render('duplicate', [
			'model' => $model,
			'extraParams' => $this->changeActionParams($params, 'duplicate', $model)
		]);
	}

	/**
	 * Updates an existing model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionUpdate($id)
	{
		$params = Yii::$app->request->queryParams;
		$model = $this->findModel($id);

		if (isset($_POST['_form_relations']) ) {
			$relations = explode(",", $_POST['_form_relations']);
		} else {
			$relations = [];
		}
		if ($model->loadAll(Yii::$app->request->post(), $relations) ) {
			if( $this->saveAll('update', $model) ) {
				if( $this->afterSave('update', $model) ) {
					$this->showFlash('update', $model);
					return $this->whereTogoNow('update', $model);
				}
			}
		}
		return $this->render('update', [
			'model' => $model,
			'extraParams' => $this->changeActionParams($params,'update', $model)
		]);
	}

	/**
	 * Deletes an existing model.
	 * @param integer $id
	 * @return mixed
	*/
	public function actionDelete($id)
	{
		$model = $this->findModel($id);
		try {
			$model->deleteWithRelated();
			if( $this->afterSave('delete', $model) ) {
				$this->showFlash('delete', $model);
				return $this->whereToGoNow('delete', $model);
			}
		} catch (\yii\db\IntegrityException $e ) {
			$msg = $e->getMessage();
			Yii::$app->session->addFlash('error', $msg);
			throw new DeleteModelException($model, $e);
		}
		return $this->whereToGoNow('delete', $model);
	}

	/**
	 * Export model information into PDF format.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionPdf($id)
	{
		$params = Yii::$app->request->queryParams;
		$model = $this->findModel($id);
		if( YII_DEBUG ) {
            Yii::$app->getModule('debug')->instance->allowedIPs = [];
        }
		// https://stackoverflow.com/a/54568044/8711400
		$content = $this->renderAjax('_pdf', [
			'model' => $model,
			'extraParams' => $this->changeActionParams($params, 'pdf', $model)
		]);
		$methods = [];
		$margin_header = AppHelper::yiiparam('pdfMarginHeader', 15);
		$margin_footer = AppHelper::yiiparam('pdfMarginFooter', 15);
		$margin_top = AppHelper::yiiparam('pdfMarginTop', 20);
		$margin_bottom = AppHelper::yiiparam('pdfMarginBottom', 20);
		if( $this->findViewFile('_pdf_header') ) {
			$header_content = $this->renderPartial('_pdf_header', ['model'=>$model]);
			// h:{00232}
			if( strncmp($header_content,'h:{',3) === 0 ) {
				$margin_top = intval(substr($header_content,3,5));
				$header_content = substr($header_content,9);
			}
			$methods['setHeader'] = $header_content;
		} else {
			$methods['setHeader'] = date('Y-m-d H:i') . '|'
				. $model->getModelInfo('title') . '|' . Yii::$app->name . ' - {PAGENO}';
		}
		if( $this->findViewFile('_pdf_footer') ) {
			$methods['setFooter'] = $this->renderPartial('_pdf_footer', ['model'=>$model]);
		}
		$pdf = new \kartik\mpdf\Pdf([
			'mode' => \kartik\mpdf\Pdf::MODE_CORE,
			'format' => \kartik\mpdf\Pdf::FORMAT_A4,
			'orientation' => \kartik\mpdf\Pdf::ORIENT_PORTRAIT,
			'destination' => \kartik\mpdf\Pdf::DEST_BROWSER,
			'marginHeader' => $margin_header, // Margin from top of page
			'marginFooter' => $margin_footer, // Margin from bottom of page
			'marginTop' => $margin_top, // Margin from top of page to content
			'marginBottom' => $margin_bottom, // $margin_footer,
			'content' => $content,
			'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
			'cssInline' => file_get_contents(Yii::getAlias('@app') . '/web/css/print.css'),
			'options' => ['title' => $model->recordDesc()],
			'methods' => $methods,
		]);
		return $pdf->render();
	}

	protected function whereToGoNow($from, $model)
	{
		$returnTo = Yii::$app->request->getBodyParam('returnTo');
		if( !$returnTo ) {
			$returnTo = Yii::$app->request->queryParams['returnTo']??null;
		}
		if( $returnTo ) {
			return $this->redirect($returnTo);
		}
		$referrer = Yii::$app->request->post("_form_referrer");
		if ( $referrer ) {
			return $this->redirect($referrer);
		}
		if ($from == 'update') {
			$redirect = ['view', 'id' => $model->getPrimaryKey()];
		} else if ($from == 'create') {
			if (Yii::$app->request->post('_and_create') == '1') {
				$redirect = ['create'];
			} else {
				$redirect = ["index"];
			}
		} else if ($from == 'duplicate') {
			$redirect = ['view', 'id' => $model->getPrimaryKey()];
		} else if ($from == 'delete') {
			$redirect = ["index"];
		} else {
			throw new Exception("Where should I go now?");
		}
		return $this->redirect($this->actionRoute($redirect));
	}

	public function addParamsToUrl($url, $params)
    {
        if ($params === null) {
            $request = Yii::$app->getRequest();
            $params = $request instanceof Request ? $request->getQueryParams() : [];
        }
        $params[0] = $url;
        return Url::to($params);
    }


	public function genBreadCrumbs($action_id, $model)
	{
		$breadcrumbs = [];
		$prefix = $this->getRoutePrefix();
		$breadcrumbs[] = [
			'label' =>  $model->getModelInfo('title_plural'),
			'url' => [ $this->id . '/index' ]
		];
		switch( $action_id ) {
			case 'update':
				$breadcrumbs[] = [
					'label' => $model->t('churros', 'Updating {title}: {record_short}'),
// 					'url' => array_merge([ $prefix . $this->id . '/view'], $model->getPrimaryKey(true))
				];
				break;
			case 'duplicate':
				$breadcrumbs[] = [
					'label' => Yii::t('churros', 'Duplicating ') . $model->recordDesc('short', 20),
					'url' => array_merge([ $prefix . $this->id . '/view'], $model->getPrimaryKey(true))
				];
				break;
			case 'view':
				$breadcrumbs[] = $model->recordDesc('short', 20);
				break;
			case 'create':
				$breadcrumbs[] = $model->t('churros', 'Creating {title}');
				break;
			case 'index':
				break;
			default:
				throw new \Exception($action_id);
		}
		return $breadcrumbs;
	}

	public function actionRoute($action_id = null)
	{
		if( $action_id === null ) {
			return $this->getRoutePrefix() . $this->id;
		} else {
			return Url::toRoute($action_id);
		}
	}

	public function masterRoute($master)
	{
		return $this->id . '/' . $master->id;
	}

	public function getRoutePrefix()
	{
		$route = $this->id;
		$route_pos = false;
		$request_url = '/' . Yii::$app->request->getPathInfo();
		if( $route_pos === false ) {
			$route_pos = strpos($request_url, $route);
		}
		$prefix = substr($request_url, 0, $route_pos);
		if( substr($prefix, -1) != '/' ) {
			$prefix .= '/';
		}
		return $prefix;
	}

	public function joinMany2ManyModels($glue, $models)
	{
		if( $models == null || count($models)==0 ) {
			return "";
		}
		$attrs = [];
		$route = null;
		foreach((array)$models as $model) {
			if( $route == null ) {
				$route = $this->getRoutePrefix() . $model->controllerName() . '/';
			}
			if( $model != null ) {
				$url = $route . strval($model->getPrimaryKey());
				$attrs[] = "<a href='$url'>" .  $model->recordDesc() . "</a>";
			}
		}
		return join($glue, $attrs);
	}

	public function joinHasManyModels($glue, $parent, $models)
	{
		if( $models == null || count($models)==0 ) {
			return "";
		}
		$keys = $parent->getPrimaryKey(true);
		$keys[0] = 'view';
		$parent_route = Url::toRoute($keys);
		$attrs = [];
		foreach((array)$models as $model) {
			if( $model != null ) {
				$url = $parent_route . '/'.  $model->controllerName() . '/' . strval($model->getPrimaryKey());
				$attrs[] = "<a href='$url'>" .  $model->recordDesc() . "</a>";
			}
		}
		return join($glue, $attrs);
	}

	// Ajax
	public function actionAutocomplete(array $fields)
	{
		$ret = [];
		static $clientIdGetParamName = 'query';
		$value = $_GET[$clientIdGetParamName];
		$searchModel = $this->findModel(null, null, 'search');
		$query = $searchModel->find();
		foreach( (array)$fields as $field ) {
			$query->orWhere( [ "like", $field, $value  ] );
		}
		foreach( $query->all() as $record) {
			$ret[] = [ 'id' => $record->id, 'value' => $record->recordDesc('long') ];
		}
		echo json_encode($ret);
	}

	// Ajax
	public function actionRawModel($id)
	{
		$model = $this->findModel($id);
		if( $model ) {
            return json_encode($model->getAttributes());
        } /// @todo else
	}

	protected function getSuccessMessage(string $action_id): string
	{
		switch( $action_id ) {
		case 'update':
			return self::MSG_UPDATED;
		case 'create':
			return self::MSG_CREATED;
		case 'delete':
			return self::MSG_DELETED;
		case 'duplicate':
			return self::MSG_DUPLICATED;
		default:
			break;
		}
	}

	protected function showFlash($action_id, $model)
	{
		$pk = $model->getPrimaryKey();
		if( is_array($pk) ) {
			$link_to_me = Url::to(array_merge([$this->actionRoute('view')], $pk));
		} else {
			$link_to_me = $this->actionRoute('view') . "/$pk";
		}
		switch( $action_id ) {
		case 'delete':
			Yii::$app->session->addFlash('success',
				$model->t('churros', $this->getSuccessMessage('delete')));
			break;
		default:
			if( ($msg = $this->getSuccessMessage($action_id)) != '' ) {
				Yii::$app->session->addFlash('success',
					strtr($model->t('churros', $msg),
						['{model_link}' => $link_to_me]));
			}
			break;
		}
	}

	protected function afterSave($action_id, $model)
	{
		return true;
	}

	protected function findViewFile($view)
	{
		if (strncmp($view, '@', 1) === 0) {
			// e.g. "@app/views/main"
			$file = Yii::getAlias($view);
		} elseif (strncmp($view, '//', 2) === 0) {
			// e.g. "//layouts/main"
			$file = Yii::$app->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
		} elseif (strncmp($view, '/', 1) === 0) {
			// e.g. "/site/index"
			$file = $this->module->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
		} else {
			$file = $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id . DIRECTORY_SEPARATOR . $view;
		}
		if (pathinfo($file, PATHINFO_EXTENSION) === '') {
			$file .= '.php';
		}
		if( !is_file($file) ) {
			return null;
		} else {
			return $file;
		}
	}

	protected function saveAll(string $context, $model, bool $in_trans = false): bool
	{
		return $model->saveAll(true, $in_trans);
	}


}
