<?php

namespace santilin\churros;

use Yii;
use yii\helpers\Url;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\HttpException;
use yii\base\ErrorException;
use santilin\churros\exceptions\{DeleteModelException,SaveModelException};
use santilin\churros\helpers\{AppHelper,FormHelper};

/**
 * CrudController implements the CRUD actions for yii2 models
 */
class CrudController extends \yii\web\Controller
{
	protected $crudActions = [];
	protected $masterModel = false;
	protected $masterController = null;

	public $accessOnlyMine = false;

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
		if ($this->getMasterModel() && !array_key_exists('master', $actionParams)) {
			$actionParams['master'] = $this->getMasterModel();
		}
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
        return parent::beforeAction($action);
	}

	/**
	 * Lists all models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		$params = Yii::$app->request->queryParams;
		$searchModel = $this->createSearchModel();
		$params['permissions'] = ($params['permissions']??true===false) ? false : $this->crudActions;
		if ($this->getMasterModel()) {
			$related_field = $searchModel->getRelatedFieldForModel($this->getMasterModel());
			$searchModel->setAttribute($related_field,
				$params[$searchModel->formName()][$related_field] = $this->getMasterModel()->getPrimaryKey());
		}
		$params = $this->changeActionParams($params, 'index', $searchModel);
		return $this->render('index', [
			'searchModel' => $searchModel,
			'indexParams' => $params,
			'indexGrids' => [ '_grid' => [ '', null, [] ] ]
		]);
	}

	public function indexDetails($master, string $view, string $search_model_class, array $params)
	{
		$detail = $this->createSearchModel($search_model_class);
		$related_field = $detail->getRelatedFieldForModel($master);
 		$params[$detail->formName()][$related_field] = $master->getPrimaryKey();
  		$detail->$related_field = $master->getPrimaryKey();
		$params['master'] = $master;
		$params['embedded'] = true;
		return $this->renderAjax($view, [
// 			'dataProvider' => $detail->search($params),
			'searchModel' => $detail,
			'indexParams' => $this->changeActionParams($params, 'index', $detail),
			'indexGrids' => [ '_grid' => [ '', null, [] ] ],
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
		$model = $this->findModel($id, $params);
		$params['permissions'] = ($params['permissions']??true===false) ? false : $this->crudActions;
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
		$params['permissions'] = ($params['permissions']??true===false) ? false : $this->crudActions;
		$model = $this->findFormModel(null, null, 'create', $params);
		if ($this->getMasterModel()) {
			$related_field = $model->getRelatedFieldForModel($this->getMasterModel());
			$model->setAttribute($related_field, $this->getMasterModel()->getPrimaryKey());
		}
		$model->scenario = 'create';

		if (isset($_POST['_form_relations'])) $relations = explode(",", $_POST['_form_relations']); else $relations = [];
		if ($model->loadAll($req->post(), $relations) ) { // ?? $req->post
			if ($model->saveAll(true) ) {
				if ($req->getIsAjax()) {
					return json_encode($model->getAttributes());
				}
				$this->addSuccessFlashes('create', $model);
				return $this->redirect($this->whereToGoNow('create', $model));
			}
		}
		return $this->render('create', [
			'model' => $model,
			'viewForms' => [ '_form' => [ '', null, $this->crudActions, '' ] ],
			'formParams' => $this->changeActionParams($params, 'create', $model)
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
		$req = Yii::$app->request;
		$params = array_merge($req->get(), $req->post());
		$model = $this->findFormModel($id, null, 'duplicate', $params);
		$model->setDefaultValues(true); // duplicating
		$model->scenario = 'duplicate';

		if (isset($_POST['_form_relations']) ) {
			$relations = explode(",", $_POST['_form_relations']);
		} else {
			$relations = [];
		}
		if ($model->loadAll($req->post(), $relations) ) {
			$model->setIsNewRecord(true);
			$model->resetPrimaryKeys();
			if( $model->saveAll(true) ) {
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
		$model = $this->findFormModel($id, null, 'update', $params);
		$model->scenario = 'update';

		if (isset($_POST['_form_relations']) ) {
			$relations = explode(",", $_POST['_form_relations']);
		} else {
			$relations = [];
		}
		if ($model->loadAll($params, $relations) && $req->isPost ) {
			if( $model->saveAll(true) ) {
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
	 * @param integer $id
	 * @return mixed
	*/
	public function actionDelete($id)
	{
		try {
			$model = $this->findFormModel($id, null, 'delete');
			$model->deleteWithRelated();
			if (Yii::$app->request->getIsAjax()) {
				return json_encode($id);
			}
			$this->addSuccessFlashes('delete', $model);
			return $this->redirect($this->whereTogoNow('delete', $model));
		} catch( ForbiddenHttpException $e ) {
			Yii::$app->session->addFlash('error', $e->getMessage());
			return $this->redirect(Yii::$app->request->referrer?:Yii::$app->homeUrl);
		} catch (\yii\db\IntegrityException $e ) {
			Yii::$app->session->addFlash('error', $model->t('churros',
				$this->getResultMessage('error_delete_integrity')));
		} catch (\yii\web\ForbiddenHttpException $e ) {
			Yii::$app->session->addFlash('error', $model->t('churros',
				$this->getResultMessage('error_delete')));
		}
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
		if( $returnTo ) {
			return $returnTo;
		}
		$redirect_params = [];
		if( !empty($_REQUEST['sort']) ) {
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
			$to = 'index';
			break;
		case 'delete':
			if (Yii::$app->request->referrer) {
				return Yii::$app->request->referrer;
			} else {
				$to = 'index';
			}
			break;
		case 'view':
		case 'index':
		default:
			$to = "index";
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
		$redirect_params[0] = $this->getActionRoute($to);
		return $redirect_params;
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


	public function genBreadCrumbs(string $action_id, $model, array $permissions = []): array
	{
		$breadcrumbs = [];
 		$master = $this->getMasterModel();
		if ($master) {
			$prefix = '/' . $this->getFullRoute() . '/' . $master->controllerName(). '/';
			$breadcrumbs[] = [
				'label' => AppHelper::mb_ucfirst($master->getModelInfo('title_plural')),
				'url' => [ $prefix . 'index']
			];
			$keys = $master->getPrimaryKey(true);
			$keys[0] = $prefix . 'view';
			$breadcrumbs[] = [
				'label' => $master->recordDesc('short', 25),
				'url' => $keys
			];
			// child
			$breadcrumbs[] = [
				'label' => AppHelper::mb_ucfirst($model->getModelInfo('title_plural')),
				'url' => $this->getActionRoute('index')
			];
			switch( $action_id ) {
				case 'update':
					$breadcrumbs[] = [
						'label' => $model->recordDesc('short', 25),
						'url' => array_merge([$this->getActionRoute('view')], $model->getPrimaryKey(true))
					];
					break;
				case 'index':
					break;
			}
		} else {
			$prefix = '/' . $this->getFullRoute();
			if (FormHelper::hasPermission($permissions, 'index')) {
				$breadcrumbs['index'] = [
					'label' =>  $model->getModelInfo('title_plural'),
					'url' => [ $this->id . '/index' ]
				];
			} else {
				$breadcrumbs['index'] = [
					'label' =>  $model->getModelInfo('title_plural'),
				];
			}
			switch( $action_id ) {
				case 'update':
					$breadcrumbs['action'] = [
						'label' => $model->t('churros', 'Updating {record_short}'),
	// 					'url' => array_merge([ $prefix . $this->id . '/view'], $model->getPrimaryKey(true))
					];
					break;
				case 'duplicate':
					$breadcrumbs['action'] = [
						'label' => Yii::t('churros', 'Duplicating ') . $model->recordDesc('short', 20),
						'url' => array_merge([ $prefix . $this->id . '/view'], $model->getPrimaryKey(true))
					];
					break;
				case 'view':
					$breadcrumbs['action'] = $model->recordDesc('short', 20);
					break;
				case 'create':
					$breadcrumbs['action'] = $model->t('churros', 'Creating {title}');
					break;
				case 'index':
					break;
				default:
					throw new \Exception($action_id);
			}
		}
		return $breadcrumbs;
	}

	private function getFullRoute(): string
	{
		return $this->module instanceof \yii\base\Application ? $this->id : $this->module->getUniqueId();
	}

  	public function getActionRoute($action_id = null, $master_model = null): string
	{
		if (!$master_model) {
			$master_model = $this->getMasterModel();
		}
		if ($master_model) {
			$controller_route = '/' . $this->getFullRoute();
			$controller_route .= '/' . $master_model->controllerName()
				. '/' . $master_model->getPrimaryKey() . '/' .  $this->id;
			if (is_array($action_id)) {
				$action_id[0] = $controller_route . '/' . $action_id[0];
				$controller_route = Url::toRoute($action_id);
			} else if ($action_id != null ) {
				$controller_route .= '/' . $action_id;
			}
			return $controller_route;
		} else if ($action_id === null) {
			return substr(Url::toRoute('r'), 0, -2);
		} else {
			return Url::toRoute($action_id);
		}
	}

// 	public function actionRoute($action_id = null): string
// 	{
// 		if( $this->master_model ) {
// 			$parent_route = $this->getRoutePrefix()
// 				. $this->id;
// 			if( $action_id ) {
// 				$action_id = (array) $action_id;
// 				if ($action_id[0] != '' && $action_id[0] == '/' ) {
// 					$action_id[0] = $parent_route . $action_id[0];
// 				} else {
// 					$action_id[0] = $parent_route . '/' . $action_id[0];
// 				}
// 				return Url::toRoute($action_id??'');
// 			} else {
// 				return $parent_route;
// 			}
// 		} else {
// 			return $this->getActionRoute($action_id, null);
// 		}
// 	}

	public function getRoutePrefix($route = null): string
	{
		if( $route === null ) {
			$route = Url::toRoute($this->id);
		}
		$request_url = '/' . Yii::$app->request->getPathInfo();
		$route_pos = strpos($request_url, $route);
		$prefix = substr($request_url, 0, $route_pos);
		if( substr($prefix, -1) != '/' ) {
			$prefix .= '/';
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

	protected function getResultMessage(string $action_id): string
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
		case 'error_delete':
			return self::MSG_ERROR_DELETE;
		case 'error_delete_integrity':
			return self::MSG_ERROR_DELETE_INTEGRITY;
		case 'access_denied':
			return self::MSG_ACCESS_DENIED;
		case 'model_not_found':
			return self::MSG_NOT_FOUND;
		case 'used_in_relation':
			return self::MSG_ERROR_DELETE_USED_IN_RELATION;
		default:
			return self::MSG_NO_ACTION;
		}
	}

	protected function addSuccessFlashes($action_id, $model, $success_message = null)
	{
		if( $success_message !== false ) {
			if( !$success_message ) {
				$success_message = $this->getResultMessage($action_id);
			}
			$success_message = $model->t('churros', $success_message);
			if( strpos($success_message, '{model_link}') !== FALSE ) {
				$link_to_model = $this->linkToModel($model);
				$success_message = str_replace('{model_link}', $link_to_model, $success_message);
			}
			Yii::$app->session->addFlash('success', $success_message);
		}
		$this->addErrorFlashes($model);
	}


	protected function addErrorFlashes($model)
	{
		foreach($model->getFirstErrors() as $error ) {
			if( strpos($error, '{model_link}') !== FALSE ) {
				$link_to_model = $this->linkToModel($model);
				$error = str_replace('{model_link}', $link_to_model, $error);
			}
			Yii::$app->session->addFlash('error', $error );
		}
		foreach($model->getFirstWarnings() as $warning ) {
			if( strpos($warning, '{model_link}') !== FALSE ) {
				$link_to_model = $this->linkToModel($model);
				$warning = str_replace('{model_link}', $link_to_model, $warning);
			}
			Yii::$app->session->addFlash('warning', $warning );
		}
	}

	protected function linkToModel($model)
	{
		$pk = $model->getPrimaryKey();
		if( is_array($pk) ) {
			$link = Url::to(array_merge([$this->getActionRoute('view')], $pk));
		} else {
			$link = $this->getActionRoute('view') . "/$pk";
		}
		return $link;
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

	public function joinMany2ManyModels(string $glue, array $models, bool $make_links = false): string
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
				if( $make_links ) {
					$url = $route . strval($model->getPrimaryKey());
					$attrs[] = "<a href='$url'>" .  $model->recordDesc() . "</a>";
				} else {
					$attrs[] = $model->recordDesc();
				}
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

	public function getMasterModel()
	{
		if ($this->masterModel === false) {
			$master_id = intval(Yii::$app->request->get('parent_id', 0));
			if ($master_id !== 0) {
				$this->masterController = Yii::$app->request->get('parent_controller');
				assert($this->masterController != '');
				$master_model_name = 'app\\models\\'. AppHelper::camelCase($this->masterController);
				$this->masterModel = $master_model_name::findOne($master_id);
				if ($this->masterModel == null) {
					throw new NotFoundHttpException($master_model->t('churros',
						"The master record of {title} with '{id}' id does not exist",
						[ '{id}' => $master_id]));
				}
			} else {
				$this->masterModel = null;
			}
		}
		return $this->masterModel;
	}

	/**
	 * @param Model $master The master model (for detail_grids)
	 * @param Model $child The child model (for detail_grids)
	 * @todo Eliminar
	 */
	public function controllerRoute($master = null, $child = null): ?string
	{
		if( $master == null ) {
			$master = $this->master_model;
		}
		$master_route = $master->controllerName();
		$ret = $this->getRoutePrefix($master_route);
		$ret .= $master->controllerName() . '/'
			. $master->getPrimaryKey() . '/';
		$ret .= $child->controllerName();
		return $ret;
	}

}
