<?php

namespace santilin\churros;

use Yii;
use yii\helpers\{Url,StringHelper};
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
	use ControllerTrait;

	static protected $_prefix = null;
	static protected $_model_name = null;
	protected $crudActions = [];
	protected $isJunctionModel = false;
	protected $masterModel = false;
	protected $masterController = null;

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
		if (!array_key_exists('master', $actionParams) && $this->getMasterModel() ) {
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
					$this->redirect($this->request->referrer ?: Yii::$app->homeUrl);
					return false;
				}
			}
        }
        if (YII_ENV_TEST && $action->id == "delete" ) {
			$this->enableCsrfValidation = false;
		}
        return parent::beforeAction($action);
	}

	public function userPermissions(): bool|array
	{
		return $this->crudActions;
	}

	/**
	 * Lists all models.
	 * @todo Revisar, no es exactamente MVC
	 * @return mixed
	 */
	public function actionIndex()
	{
		$params = $this->request->queryParams;
		$searchModel = $this->createSearchModel();
		if (!$searchModel) {
			throw new NotFoundHttpException("Unable to create a searchModel for $this->id crud controller");
		}
		$params['permissions'] = $this->resolvePermissions($params['permissions']??[], $this->userPermissions());
		if ($this->getMasterModel()) {
			$related_field = $searchModel->relatedFieldForModel($this->getMasterModel());
			if (is_array($related_field)) { // many2many
			} else {
				$searchModel->setAttribute($related_field,
				$params[$searchModel->formName()][$related_field] = $this->getMasterModel()->getPrimaryKey());
			}
		}
		$params = $this->changeActionParams($params, 'index', $searchModel);
		return $this->render('index', [
			'searchModel' => $searchModel,
			'indexParams' => $params,
			'indexGrids' => [ '_grid' => [ '_grid', '', null, [], [], [] ] ]
		]);
	}

	/**
	 * @param array $params 'parentPermissions' => parent permissions
	 */
	public function indexDetails($master, string $view, array $params, $previous_context = null, string $search_model_class = null)
	{
		$this->action = $this->createAction('index');
		// Tiene preferencia la clase más específica
		if ($search_model_class && class_exists("$search_model_class{$view}_Search")) {
			$detail = $this->createSearchModel("$search_model_class{$view}_Search");
		} else if ($search_model_class && class_exists("{$search_model_class}_Search")) {
			$detail = $this->createSearchModel("{$search_model_class}_Search");
		} else {
			$detail = $this->createSearchModel($search_model_class);
		}
		if (!$detail) {
			throw new \Exception("No {$search_model_class}_Search nor $search_model_class{$view}_Search class found in CrudController::indexDetails");
		}
		$params['permissions'] = $this->resolvePermissions([], $this->userPermissions());
		$related_field = $detail->relatedFieldForModel($master);
		if (is_array($related_field)) { // many2many
			$params['_search_relations'] = [ $related_field[0] ];
			$params[$detail->formName()][$related_field[1]] = $master->getPrimaryKey();
		} else {
			$params[$detail->formName()][$related_field] = $master->getPrimaryKey();
			$detail->$related_field = $master->getPrimaryKey();
		}
		$params['master'] = $master;
		$params['embedded'] = true;
		$params['previous_context'] = $previous_context;
		$this->layout = false;
		return $this->render($view, [
			'searchModel' => $detail,
			'indexParams' => $this->changeActionParams($params, 'index', $detail),
			'indexGrids' => [ '_grid' => [ '_grid', '', null, [], [], [] ] ],
			'gridName' => $view,
			'gridPerms' => $params['permissions'],
		]);
	}


	/**
	 * Displays a single model.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionView($id)
	{
		$params = $this->request->queryParams;
		$model = $this->findModel($id, $params);
		$params['permissions'] = $this->resolvePermissions($params['permissions']??[], $this->userPermissions());
		if ($this->request->getIsAjax()) {
			$this->layout = false;
			return $this->render('_view', [
				'model' => $model,
				'viewForms' => [ '_view' => [ '', null, [], '' ] ],
				'viewParams' => $this->changeActionParams($params, 'view', $model)
			]);
		} else {
			return $this->render('view', [
				'model' => $model,
				'viewForms' => [ '_view' => [ '', null, [], '' ] ],
				'viewParams' => $this->changeActionParams($params, 'view', $model)
			]);
		}
	}

	/**
	 * Creates a new model.
	 * @return mixed
	 */
	public function actionCreate()
	{
		$params = array_merge($this->request->get(), $this->request->post());
		$params['permissions'] = $this->resolvePermissions($params['permissions']??[], $this->userPermissions());
		$model = $this->findFormModel(null, null, 'create', $params);
		$model->scenario = 'create';

		$relations = empty($params['_form_relations'])?[]:explode(",", $params['_form_relations']);
		if ($model->loadAll($params, $relations) ) {
			if ($model->saveAll(true) ) {
				if ($this->request->getIsAjax()) {
					return json_encode($model->getAttributes());
				}
				$this->addSuccessFlashes('create', $model);
				return $this->redirect($this->returnTo(null, 'create', $model));
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
		$params = array_merge($this->request->get(), $this->request->post());
		$params['permissions'] = $this->resolvePermissions($params['permissions']??[], $this->userPermissions());
		$model = $this->findFormModel($id, null, 'duplicate', $params);
		$model->scenario = 'duplicate';

		$relations = empty($params['_form_relations'])?[]:explode(",", $params['_form_relations']);
		if ($model->loadAll($this->request->post(), $relations) ) {
			$model->setIsNewRecord(true);
			$model->resetPrimaryKeys();
			if( $model->saveAll(true) ) {
				if ($this->request->getIsAjax()) {
					return json_encode($model->getAttributes());
				}
				$this->addSuccessFlashes('duplicate', $model);
				return $this->redirect($this->returnTo(null, 'duplicate', $model));
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
		$params = array_merge($this->request->get(), $this->request->post());
		$params['permissions'] = $this->resolvePermissions($params['permissions']??[], $this->userPermissions());
		$model = $this->findFormModel($id, null, 'update', $params);
 		if ($model === null && FormHelper::hasPermission($params['permissions'], 'create')) {
			return $this->redirect(array_merge(['create'], $params));
		}
		$model->scenario = 'update';

		$relations = empty($params['_form_relations'])?[]:explode(",", $params['_form_relations']);
		if ($model->loadAll($params, $relations)) {
			if ($model->saveAll(true)) {
				if ($this->request->getIsAjax()) {
					\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
					return [
						'data' => $model->getAttributes(),
						'success_message' => $model->t('churros', $this->getResultMessage('update')),
					];
				}
				$this->addSuccessFlashes('update', $model);
				return $this->redirect($this->returnTo(null, 'update', $model));
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
		$model = $this->findModel($id);
		if (!in_array('delete', $this->crudActions)) {
			throw new ForbiddenHttpException($model->t('churros',
				$this->getResultMessage('access_denied')));
		}
		try {
			if ($model->deleteWithRelated()) {
				if ($this->request->getIsAjax()) {
					return json_encode($id);
				}
				$this->addSuccessFlashes('delete', $model);
				return $this->redirect($this->returnTo(null, 'delete', $model));
			} else {
				Yii::$app->session->addFlash('error', $model->t('churros', $this->getResultMessage('error_delete')));
				$this->addErrorFlashes($model);
			}
		} catch (\yii\db\IntegrityException $e) {
			$model->addError('delete', $model->t('churros',
				$this->getResultMessage('error_delete_integrity')));
			if (YII_ENV_DEV) {
				$model->addError('delete_integrity', $e->getMessage());
			}
		} catch (\yii\web\ForbiddenHttpException $e ) {
			$model->addError('delete', $model->t('churros',
				$this->getResultMessage('error_delete')));
		}
		$this->addErrorFlashes($model);
		return $this->redirect($this->returnTo(null, 'delete_error', $model));
	}

	/**
	 * Export model information into PDF format.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionPdf($id)
	{
		$params = $this->request->queryParams;
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

	protected function returnTo(?string $to, string $from, $model, array $redirect_params = []): string|array
	{
		$returnTo = $this->request->post('returnTo');
		if( !$returnTo ) {
			$returnTo = $this->request->queryParams['returnTo']??null;
		}
		if( $returnTo ) {
			return $returnTo;
		}
		if (empty($to)) {
			if ($this->isJunctionModel && $this->getMasterModel()) {
				$to_model = 'parent';
				$to_action = 'view';
			} else {
				$to_model = null;
				switch ($from) {
					case 'create':
						if ($this->request->post('_and_create') == '1') {
							$to_action = 'create';
						} else {
							$to_action = 'view';
						}
						break;
					case 'duplicate':
						if ($this->request->post('_and_create') == '1') {
							$to_action = 'duplicate';
						} else {
							$to_action = 'view';
						}
						break;
					case 'update':
					case 'delete_error':
						$to_action = 'view';
						break;
					case 'delete':
					case 'view':
					case 'index':
					case '':
						$to_action = 'index';
						break;
					default:
						$to_action = $from;
				}
			}
		} else {
			list($to_model, $to_action) = AppHelper::splitString($to, '.');
		}
		if ($to_model) {
			if ($to_model == 'parent') {
				if ($this->getMasterModel()) {
					$model = $this->masterModel;
				}
			} else if ($to_model == 'model') {
			} else {
				$model =$$to_model;
			}
		}
		$pk = $model->getPrimaryKey(true);
		switch($to_action) {
			case 'view':
			case 'update':
			case 'duplicate':
				$redirect_params = array_merge($redirect_params, $pk);
				// no break
			case 'create':
// 				if( isset($_REQUEST['_form_cancelUrl']) ) {
// 					$redirect_params['_form_cancelUrl'] = $_REQUEST['_form_cancelUrl'];
// 				}
				break;
			case 'index':
				break;
			default:
				$redirect_params = array_merge($redirect_params, $pk);
		}
		if ($to_model) {
			$redirect_params[0] = Url::to('/' . $this->getBaseRoute() . '/' . $model->controllerName()
				. '/' . $to_action, $pk);
		} else {
			$redirect_params[0] = $this->getActionRoute($to_action);
		}
		if (!array_key_exists('sort', $redirect_params) && !empty($_REQUEST['sort'])) {
			$redirect_params['sort'] = $_REQUEST['sort'];
		}
		return $redirect_params;
	}


	/**
	 * @deprecated
	 */
	protected function whereToGoNow(string $from, $model)
	{
		die(__FUNCTION__ . "Deprecated");
	}

	/**
	 * @param $model not used, for compatibility with JsonController
	 */
  	public function getActionRoute($action_id = null, $model = null, $master_model = null): string
	{
		if ($master_model == null) {
			$master_model = $this->getMasterModel();
		}
		if ($master_model) {
			$controller_route = $this->getBaseRoute();
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


	// Ajax
	public function actionAutocomplete(string $search, string $result, array $fields = [], string $format = 'long')
	{
		$ret = [];
		$searchModel = $this->createSearchModel();
		if (empty($fields)) {
			$fields = $searchModel->findCodeAndDescFields();
		}
		$fld_values = [];
		foreach ($fields as $field) {
			$fld_values[$field] = $search;
		}
		$dataProvider = $searchModel->search([$searchModel->formName() => $fld_values, 'or' => true ]);
		if ($result == 'select2') {
			foreach ($dataProvider->getModels() as $record) {
				$ret[] = [ 'id' => $record->getPrimaryKey(), 'text' => $record->recordDesc($format) ];
			}
			echo json_encode([ 'results' => $ret ]);
		}
	}

	// Ajax
	public function actionRawModel($id)
	{
		$params = $this->request->queryParams;
		$model = $this->findModel($id, $params);
		if( $model ) {
            return json_encode($model->getAttributes());
        } /// @todo else
	}

	// Ajax for the MutiColumnTypeAhead control
	public function actionMultiAutocomplete(string $search, string $fields, int $page = 1, int $per_page = 12)
	{
		\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		$searchModel = $this->createSearchModel();
		$conds = [];
		$array_fields = explode(',',$fields);
		foreach ($array_fields as $field) {
			$conds[$field] = $search;
		}
		$indexParams = [
			'or' => true,
			 $searchModel->formName() => $conds
		];
		$dataProvider = $searchModel->search($indexParams);
		$dataProvider->query
				->select($array_fields)
				->limit($page, $per_page);
// 				->asArray();
		if ($dataProvider->getTotalCount()) {
			return $dataProvider->getModels();
		}
		return [];
	}

	public function getMasterModel()
	{
		if ($this->masterModel === false) {
			$master_id = intval($this->request->get('parent_id', 0));
			if ($master_id !== 0) {
				$this->masterController = $this->request->get('parent_controller');
				assert($this->masterController != '');
				$master_model_name = 'app\\models\\'. AppHelper::camelCase($this->masterController);
				$this->masterModel = $master_model_name::findOne($master_id);
				if ($this->masterModel == null) {
					throw new NotFoundHttpException(Yii::t('churros',
						"The master record of {title} with '{id}' id does not exist",
						[ 'id' => $master_id, 'title' => $master_model_name ]));
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

    public function genBaseBreadCrumbs(string $action_id, $model, array $view_params= []): array
	{
		$scenario = $model->scenario?:$action_id;
		$permissions = $view_params['permissions']??[];
		$breadcrumbs = [];
		$master = $this->getMasterModel();
		if ($master) {
			$prefix = $this->getBaseRoute() . '/' . $master->controllerName(). '/';
			$breadcrumbs[] = [
				'label' => StringHelper::mb_ucfirst($master->getModelInfo('title_plural')),
				'url' => [ $prefix . 'index']
			];
			$keys = $master->getPrimaryKey(true);
			$keys[0] = $prefix . 'view';
			$breadcrumbs[] = [
				'label' => $master->recordDesc('short', 25),
				'url' => $keys
			];
			$breadcrumbs[] = [
				'label' => StringHelper::mb_ucfirst($model->getModelInfo('title_plural')),
				'url' => $this->getActionRoute('index')
			];
		} else {
			if (FormHelper::hasPermission($permissions, 'index')) {
				$breadcrumbs[] = [
					'label' =>  $model->getModelInfo('title_plural'),
					'url' => [ $this->id . '/index' ]
				];
			// } else {
			// 	$breadcrumbs[] = [
			// 		'label' =>  $model->getModelInfo('title_plural'),
			// 	];
			}
		}
		$prim_keys = $model->getPrimaryKey(true);
		if (empty($model->getPrimaryKey())) { // create, index or duplicate
			$has_prim_keys = false;
			foreach ($prim_keys as $k => $kv) {
				if (!empty($view_params[$k])) {
					$has_prim_keys = true;
					$prim_keys[$k] = $view_params[$k];
				}
			}
			if (!$has_prim_keys) {
				$prim_keys = [];
			}
		}
		if (!empty($prim_keys)) {
			$breadcrumbs[] = [
				'label' => $model->recordDesc('short', 25),
				'url' => $scenario!='view' ? array_merge([$this->getActionRoute('view')], $prim_keys) : null,
			];
		}
		return $breadcrumbs;
	}

	public function genBreadCrumbs(string $action_id, $model, array $permissions = []): array
	{
		$breadcrumbs = $this->genBaseBreadCrumbs($action_id, $model, $permissions);
		$scenario = $model->scenario?:$action_id;
		$master = $this->getMasterModel();
		if ($master) {
			switch( $scenario ) {
				case 'update':
					$breadcrumbs[] = [
						'label' => $model->recordDesc('short', 25),
						'url' => array_merge([$this->getActionRoute('view')], $model->getPrimaryKey(true))
					];
				case 'create':
					$breadcrumbs[] = $model->t('churros', 'Creating {title}');
					break;
				case 'index':
					break;
			}
		} else {
			$prefix = $this->getBaseRoute();
			switch( $scenario ) {
				case 'update':
					$breadcrumbs[] = [
						'label' => $model->t('churros', 'Updating {record_short}'),
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
					throw new \Exception($scenario);
			}
		}
		return $breadcrumbs;
	}

	protected function linkToModel($model)
	{
		$pk = $model->getPrimaryKey();
		if( is_array($pk) ) {
			$link = Url::to(array_merge([$this->getActionRoute('view')], $pk));
		} else {
			$link = Url::to([$this->getActionRoute('view'), 'id' => $pk]);
		}
		return $link;
	}


}
