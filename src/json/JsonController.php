<?php

namespace santilin\churros\json;

use Yii;
use yii\helpers\Url;
use yii\filters\VerbFilter;
use yii\web\{HttpException,NotFoundHttpException};
use yii\base\{InvalidArgumentException,ErrorException};
use santilin\churros\ControllerTrait;
use santilin\churros\exceptions\{DeleteModelException,SaveModelException};
use santilin\churros\helpers\{AppHelper,FormHelper};

/**
 * CrudController implements the CRUD actions for yii2 models
 *
 * There is no MasterModel
 */
class JsonController extends \yii\web\Controller
{
	use \santilin\churros\ControllerTrait;

	public $accessOnlyMine = false;
	protected $crudActions = [];
	protected $root_model = false;
	protected $_root_id = null;
	protected $_path = null;
	protected $root_json_field = null;

 	/** @var The start of the json path in the current url. If null, the url is substracted the root part */
	protected $_path_start = null;


	const MSG_DEFAULT = 'The action on {la} {title} <a href="{model_link}">{record_medium}</a> has been successful.';
	const MSG_NO_ACTION = 'The action on {La} {title} <a href="{model_link}">{record_medium}</a> has been successful.';
	const MSG_CREATED = '{La} {title} <a href="{model_link}">{record_medium}</a> has been successfully created.';
	const MSG_UPDATED = '{La} {title} <a href="{model_link}">{record_medium}</a> has been successfully updated.';
	const MSG_DELETED = '{La} {title} <strong>{record_long}</strong> has been successfully deleted.';
	const MSG_ERROR_DELETE = 'There has been an error deleting {la} {title} <a href="{model_link}">{record_medium}</a>';
	const MSG_DUPLICATED = '{La} {title} <a href="{model_link}">{record_medium}</a> has been successfully duplicated.';
	const MSG_ERROR_DELETE_INTEGRITY = 'Unable to delete {la} {title} <a href="{model_link}">{record_medium}</a> because it has related data.';
	const MSG_ERROR_DELETE_USED_IN_RELATION = 'Unable to delete {la} {title} <a href="{model_link}">{record_medium}</a> because it is used by at least one {relation_title}.';
	const MSG_ACCESS_DENIED = 'Access denied to this {title}.';
	const MSG_NOT_FOUND = '{Title} with primary key {id} not found.';

	/**
	 * An array of extra params to pass to the views
	 */
	protected function changeActionParams(array $actionParams, string $action_id, $model)
	{
		return $actionParams;
	}

	public function behaviors()
	{
		$ret = [];
		$ret['verbs'] = [
			'class' => VerbFilter::className(),
			'actions' => [
				'delete' => ['post'],
			],
		];
		// Auth behaviors must be set on descendants of this controller
		return array_merge($ret, parent::behaviors());
	}

	public function beforeAction($action)
	{
        if( count($_POST) == 0 && count($_FILES) == 0 && isset($_SERVER['CONTENT_TYPE'])
			&& substr($_SERVER['CONTENT_TYPE'], 0, 19) == 'multipart/form-data' ) {
			if( $this->request->getMethod() === 'POST' && isset($_SERVER['CONTENT_LENGTH']) ) {
				if( intval($_SERVER['CONTENT_LENGTH'])>0 ) {
					Yii::$app->session->addFlash('error', strtr(Yii::t('churros', 'PHP discarded POST data because of request exceeding either post_max_size={post_size} or upload_max_filesize={upload_size}'), ['{post_size}' => ini_get('post_max_size'), '{upload_size}' => ini_get('upload_max_filesize')]));
					$this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
					return false;
				}
			}
        }
        if (YII_ENV_TEST && $action->id == "delete" ) {
			$this->enableCsrfValidation = false;
		}
		$this->getRootModel();
        return parent::beforeAction($action);
	}

	/**
	 * Lists all models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		$params = Yii::$app->request->queryParams;
		$searchModel = $this->createSearchModel($this->getPath());
		if ($searchModel === null) {
			$searchModel = $this->createSearchModel($this->getPath(), $this->_model_name . '_Search');
		}
		if (!$searchModel) {
			throw new InvalidArgumentException("No searchModel found for " . $this->id . " controller");
		}
		$params['permissions'] = FormHelper::resolvePermissions($params['permissions']??[], $this->crudActions);
		$params = $this->changeActionParams($params, 'index', $searchModel);
		return $this->render('index', [
			'searchModel' => $searchModel,
			'indexParams' => $params,
			'indexGrids' => [ '_grid' => [ '', null, [] ] ]
		]);
	}

	public function indexDetails($master, string $view, string $search_model_class,
								 array $index_params, $previous_context = null)
	{
		$this->action = $this->createAction($previous_context->action->id);
		$detail = $this->createSearchModel($master->fullPath(), "$search_model_class{$view}_Search");
		if (!$detail) {
			$detail = $this->createSearchModel($master->fullPath(), "{$search_model_class}_Search");
		}
		if (!$detail) {
			$detail = $this->createSearchModel($master->fullPath());
		}
		if (!$detail) {
			throw new \Exception("No {$search_model_class}_Search nor $search_model_class{$view}_Search class found in CrudController::indexDetails");
		}
		$related_field = $detail->getRelatedFieldForModel($master);
		$index_params[$detail->formName()][$related_field] = $master->getPrimaryKey();
		$detail->$related_field = $master->getPrimaryKey();
		$index_params['master'] = $master;
		$index_params['embedded'] = true;
		$index_params['previous_context'] = $previous_context;
		$this->layout = false;
		return $this->render($view, [
			'searchModel' => $detail,
			'indexParams' => $this->changeActionParams($index_params, 'index', $detail),
			'indexGrids' => [ '_grid' => [ '', null, [] ] ],
			'gridName' => $view,
		]);
	}


	/**
		* Displays a single model.
		* @param integer $id
		* @return mixed
		*/
	public function actionView($id)
	{
		$params = Yii::$app->request->queryParams;
		$params['permissions'] = FormHelper::resolvePermissions($params['permissions']??[], $this->crudActions);
		$model = $this->findModel($this->getPath(), $id, $params);
		return $this->render('view', [
			'model' => $model,
			'viewForms' => [ '_view' => [ '', null, [], '' ] ],
			'viewParams' => $this->changeActionParams($params, 'view', $model)
		]);
	}

	/**
	 * Creates a new model.
	 * @return mixed
	 */
	public function actionCreate()
	{
		$req = Yii::$app->request;
		$params = array_merge($req->get(), $req->post());
		$params['permissions'] = FormHelper::resolvePermissions($params['permissions']??[], $this->crudActions);
		$model = $this->findFormModel($this->getPath(), null, null, 'create', $params);
		$relations = empty($params['_form.relations'])?[]:explode(",", $params['_form.relations']);
		$model->scenario = 'create';
		if ($model->loadAll($params, $relations) ) {
			$model->setIsnewRecord(true);
			if ($model->validate() && $model->save(false) ) {
				if ($req->getIsAjax()) {
					return json_encode($model->getAttributes());
				}
				$this->addSuccessFlashes('create', $model);
				return $this->redirect($this->whereToGoNow('create', $model));
			}
		}
		return $this->render('create', [
			'model' => $model,
			'viewForms' => [ '_form' => [ '', null, [], '' ] ],
			'formParams' => $this->changeActionParams($params, 'create', $model)
		]);
	}

	/**
	 * Creates a new model by another data,so user don't need to input all field from scratch.
	 *
	 * @param mixed $id
	 * @return mixed
	 */
	public function actionDuplicate($id)
	{
		$req = Yii::$app->request;
		$params = array_merge($req->get(), $req->post());
		$params['permissions'] = FormHelper::resolvePermissions($params['permissions']??[], $this->crudActions);
		$model = $this->findFormModel($this->getPath(), $id, null, 'duplicate', $params);
		$model->setDefaultValues(true); // duplicating
		$relations = empty($params['_form.relations'])?[]:explode(",", $params['_form.relations']);
		$model->scenario = 'duplicate';
		if ($model->loadAll($params, $relations) ) {
			$model->setIsNewRecord(true);
			if ($model->validate() && $model->save(false)) {
				if ($req->getIsAjax()) {
					return json_encode($model->getAttributes());
				}
				$this->addSuccessFlashes('duplicate', $model);
				return $this->redirect($this->whereTogoNow('duplicate', $model));
			}
		}
		return $this->render('duplicate', [
			'model' => $model,
			'viewForms' => [ '_form' => [ '', null, [], '' ] ],
			'formParams' => $this->changeActionParams($params, 'duplicate', $model)
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
		$req = Yii::$app->request;
		$params = array_merge($req->get(), $req->post());
		$params['permissions'] = FormHelper::resolvePermissions($params['permissions']??[], $this->crudActions);
		$model = $this->findFormModel($this->getPath(), $id, null, 'update', $params);
		$relations = empty($params['_form.relations'])?[]:explode(",", $params['_form.relations']);

		if ($model->loadAll($params, $relations) && $req->isPost ) {
			if ($model->validate() && $model->save(false) ) {
				if ($req->getIsAjax()) {
					return json_encode($model->getAttributes());
				}
				$this->addSuccessFlashes('update', $model);
				return $this->redirect($this->whereTogoNow('update', $model));
			}
		}
		return $this->render('update', [
			'model' => $model,
			'viewForms' => [ '_form' => [ '', null, [], '' ] ],
			'formParams' => $this->changeActionParams($params, 'update', $model)
		]);
	}

	/**
	 * Deletes an existing model.
	 * @param string $path
	 * @return mixed
	*/
	public function actionDelete(string $id)
	{
		$model = $this->findFormModel($this->getPath(), $id, null, 'delete');
		try {
			if ($model->delete()) {
				if (Yii::$app->request->getIsAjax()) {
					return json_encode($path);
				}
				$this->addSuccessFlashes('delete', $model);
				return $this->redirect($this->whereTogoNow('delete', $model));
			} else {
				Yii::$app->session->addFlash('error', $model->t('churros', $this->getResultMessage('error_delete')));
				$this->addErrorFlashes($model);
			}
		} catch (\yii\db\IntegrityException $e ) {
			Yii::$app->session->addFlash('error', $model->t('churros',
				$this->getResultMessage('error_delete_integrity')));
			if (YII_ENV_DEV) {
				$this->addErrorFlashes($model);
			}
			return $this->redirect($this->whereTogoNow('delete_error', null));
		} catch( \yii\web\ForbiddenHttpException $e ) {
			Yii::$app->session->addFlash('error', $model->t('churros',
				$this->getResultMessage('access_denied')));
			if (YII_ENV_DEV) {
				$this->addErrorFlashes($model);
			}
		}
		return $this->redirect($this->whereTogoNow('delete_error', null));
	}

	/**
	 * Export model information into PDF format.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionPdf($id)
	{
		$params = Yii::$app->request->queryParams;
		$model = $this->findModel($id, $params);
		if( YII_DEBUG ) {
            Yii::$app->getModule('debug')->instance->allowedIPs = [];
        }
		// https://stackoverflow.com/a/54568044/8711400
		$content = $this->renderAjax('_pdf', [
			'model' => $model,
			'viewParams' => $this->changeActionParams($params, 'pdf', $model)
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

	protected function whereToGoNow(string $from, $model)
	{
		$returnTo = Yii::$app->request->post('returnTo');
		if( !$returnTo ) {
			$returnTo = Yii::$app->request->queryParams['returnTo']??null;
		}
		if( !$returnTo ) {
			$returnTo = Yii::$app->request->post('_form_returnTo');
		}
		if( $returnTo ) {
			return $returnTo;
		}
		if ($from == 'delete_error') {
			return Yii::$app->request->referrer;
		}
		$redirect_params = [];
		if (!empty($_REQUEST['sort']) ) {
			$redirect_params['sort'] = $_REQUEST['sort'];
		}
		switch ($from) {
		case 'create':
			if (Yii::$app->request->post('_and_create') == '1') {
				$to = 'create';
			} else {
				$to = 'view';
			}
			break;
		case 'duplicate':
			if (Yii::$app->request->post('_and_create') == '1') {
				$to = 'duplicate';
			} else {
				$to = 'view';
			}
			break;
		case 'update':
			$to = 'view';
			break;
		case 'view':
		case 'index':
		default:
			$to = "index";
			break;
		case 'delete':
			$to = 'view';
			$new_model = $model->parentModel();
			if ($new_model) {
				$model = $new_model;
			}
		}
		switch($to) {
		case 'view':
		case 'update':
		case 'duplicate':
			$redirect_params = array_merge($redirect_params, [ 'id' => $model->getPrimaryKey()]);
			// no break
		case 'create':
			if( isset($_REQUEST['_form_cancelUrl']) ) {
				$redirect_params['_form_cancelUrl'] = $_REQUEST['_form_cancelUrl'];
			}
			break;
		default:
		}
		$redirect_params[0] = $this->getActionRoute($to, $model, $this->getRootModel());
		if ($this->getRootModel()) {
			$redirect_params['root_model'] = basename(str_replace('\\', '/', get_class($this->getRootModel())));
			$redirect_params['root_id'] = $this->getRootModel()->getPrimaryKey();
			$redirect_params['root_field'] = $this->root_json_field;
		}
		return $redirect_params;
	}

	public function getActionRoute(?string $action_id, $model, $master_model = null): string
	{
		if ($action_id) {
			if (!$master_model) {
				$route = $this->getRoutePrefix($this->getPath(), false)
					. $model->getPath();
			} else {
				$route = $this->getRoutePrefix($this->getPath(), false)
					. $model->getPath();
			}
// 			$route .= '/' . $model->getJsonId() ?: $model->getPrimaryKey();
			$route .= '/' . $action_id;
			return $route;
		} else {
			if (!$master_model) {
				$route = $this->getRoutePrefix($this->getPath(), false)
					. $model->getPath();
			} else {
				$route = $this->getRoutePrefix($this->getPath(), false)
					. $model->getPath() . '/' . $model->jsonPath();
			}
		}
		return $route;
	}

	public function getRoutePrefix($route = null, bool $add_slash = true): string
	{
		$request_url = '/' . Yii::$app->request->getPathInfo();
		$route_pos = strpos($request_url, $route);
		$prefix = substr($request_url, 0, $route_pos);
		if ($add_slash) {
			if( substr($prefix, -1) != '/' ) {
				$prefix .= '/';
			}
		}
		return $prefix;
	}

	// Ajax
	public function actionAutocomplete(array $fields)
	{
		$ret = [];
		static $clientIdGetParamName = 'query';
		$value = $_GET[$clientIdGetParamName];
		$searchModel = $this->createSearchModel();
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
		$params = Yii::$app->request->queryParams;
		$model = $this->findModel($id, $params);
		if( $model ) {
            return json_encode($model->getAttributes());
        } /// @todo else
	}


	public function getPath()
	{
		if ($this->root_model === false ) {
			$this->getRootModel();
		}
		return $this->_path;
	}

	public function getMasterModel()
	{
		return $this->getRootModel();
	}

	public function getRootModel()
	{
		if ($this->root_model !== false) {
			return $this->root_model;
		}
		$req = Yii::$app->request;
		$this->_root_id = $req->post('root_id')?:$req->get('root_id');
		$root_model_name = $req->post('root_model')?:$req->get('root_model');
		if ($root_model_name) {
			$root_model_name = 'app\\models\\'. AppHelper::camelCase($root_model_name);
		}
		if ($this->_root_id) {
			$this->root_json_field = $req->post('root_jf')?:$req->get('root_jf')?:'json';
			$this->root_model = $root_model_name::findOne($this->_root_id);
			if ($this->root_model == null) {
				throw new NotFoundHttpException(Yii::t('churros',
					"The root json record for '$root_model_name' does not exist"));
			}
		}
		$this->_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		if ($this->_path) {
			$pos_path = false;
			if ($this->_path_start) {
				$pos_path = strpos($this->_path, "/{$this->_path_start}/");
			}
			// Remove root model part
			if ($pos_path === false) {
				if ($root_model_name) {
					$root_controller = $root_model_name::getModelInfo('controller_name');
					$pos_path = strpos($this->_path, "/$root_controller/");
					if ($pos_path !== false) {
						$pos_path += strlen("/$root_controller/") + 1;
						while ($pos_path < strlen($this->_path) && $this->_path[$pos_path] != '/') {
							$pos_path++;
						}
					}
				}
			}
			if ($pos_path !== false) {
				$this->_path = substr($this->_path, $pos_path);
			}
			if ($this->action) {
				// Remove action part to get only the json path
				$pos_action = strrpos($this->_path, '/'. $this->action->id . '/');
				if ($pos_action === false) {
					$action_len = strlen($this->action->id)+1;
					if (substr($this->_path,-$action_len) == '/'.$this->action->id) {
						$pos_action = strlen($this->_path) - $action_len;
					}
				}
				if ($pos_action !== false) {
					$this->_path = substr($this->_path, 0, $pos_action);
				}
			}
		}
		return $this->root_model;
	}

	public function genBaseBreadCrumbs(string $action_id, $model, array $permissions = []): array
	{
		$breadcrumbs = [];
		$master = $this->getMasterModel();
		$path_parts = explode('/',$model->getPath());
		if ($master) {
			$prefix = $this->getBaseRoute() . '/' . $master->controllerName(). '/';
			$breadcrumbs[] = [
				'label' => AppHelper::mb_ucfirst($master->getModelInfo('title_plural')),
				'url' => [ $prefix . 'index']
			];
			$keys = $master->getPrimaryKey(true);
			$keys[0] = $prefix;
			$master_keys = $keys;
			$master_keys[0] .= 'jsedit';
			$breadcrumbs[] = [
				'label' => $master->recordDesc('short', 25),
				'url' => $master_keys
			];
		} else {
			if (FormHelper::hasPermission($permissions, 'index') && $action_id != 'index') {
				$breadcrumbs[] = [
					'label' =>  $model->getModelInfo('title_plural'),
					'url' => [ $this->id . '/index' ]
				];
			} else {
				$breadcrumbs[] = [
					'label' =>  $model->getModelInfo('title_plural'),
				];
			}
		}
		$partial_path = Url::to($keys) . '/';
		for ($p=1; $p<count($path_parts)-1; $p++) {
			$partial_path .= $path_parts[$p] . '/';
			$breadcrumbs[] = [
				'label' => $path_parts[$p],
 				'url' => $partial_path . ( ($p%2)? 'index' : 'view')
			];
		}
 		if ($action_id != 'index' && $action_id != 'create') {
 			$breadcrumbs[] = [
 				'label' => $model->getJsonId(),
 				'url' => $action_id!='view' ? array_merge([$this->getActionRoute('view', $model)], $model->getPrimaryKey(true)) : null,
 			];
 		}
		return $breadcrumbs;
	}

}
