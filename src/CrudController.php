<?php

namespace santilin\churros;

use Yii;
use yii\helpers\Url;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\base\ErrorException;
use SaveModelException;
use DataException;
use santilin\churros\helpers\AppHelper;

/**
 * CrudController implements the CRUD actions for yii2 models
 */
class CrudController extends \yii\web\Controller
{
	protected $parent_model = null;
	protected $parent_controller = null;
	protected $allowedActions = [];
	public $accessOnlyOwner = false;
	const MSG_CREATED = "{La} {title} <a href=\"{model_link}\">{record_long}</a> has been successfully created.";
	const MSG_UPDATED = "{La} {title} <a href=\"{model_link}\">{record_long}</a> has been successfully updated.";
	const MSG_DELETED = "{La} {title} {record_long} has been successfully deleted.";
	const MSG_DUPLICATED = "{La} {title} <a href=\"{model_link}\">{record_long}</a> has been successfully duplicated.";

	/**
	 * An array of extra params to pass to the views
	 **/
	protected function changeActionParams($queryParams, $action_id, $model)
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
		return $ret;
	}

	public function beforeAction($action)
	{
		if (!parent::beforeAction($action)) {
			return false;
		}
		$this->getParentFromRequest();
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
		$searchModel = $this->createSearchModel();
		if( $this->parent_model ) {
			$params[$searchModel->formName()][$searchModel->getRelatedFieldForModel($this->parent_model)]
				= $this->parent_model->getPrimaryKey();
		}
		return $this->render('index', [
			'searchModel' => $searchModel,
			'parent' => $this->parent_model,
			'gridParams' => $this->changeActionParams($params, 'index', $searchModel),
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
		if( $this->accessOnlyOwner ) {
			if( !$model->IAmOwner() ) {
				throw new \yii\web\ForbiddenHttpException(
					$model->t('churros', "You can't view {esta} {title} because you are not the author"));
			}
		}
		$params = Yii::$app->request->queryParams;
		return $this->render('view', [
			'model' => $model,
			'parent' => $this->parent_model,
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
		$model = $this->findModel();
		if (isset($_POST['_form_relations']) ) {
			$relations = explode(",", $_POST['_form_relations']);
		} else {
			$relations = [];
		}
		if ($model->loadAll(Yii::$app->request->post(), $relations) ) {
			if( $this->parent_model) {
				$model->setAttribute( $model->getRelatedFieldForModel($this->parent_model), $this->parent_model->getPrimaryKey());
			}
			if( $this->doSave($model) ) {
				if( $this->afterSave('create', $model) ) {
					$this->showFlash('create', $model);
					return $this->whereToGoNow('create', $model);
				}
			}
		}
		return $this->render('create', [
			'model' => $model,
			'parent' => $this->parent_model,
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
			$model = $this->findModel($id);
			$model->setDefaultValues(true); // duplicating
		} else {
			$model = $this->findModel($id);
		}

		if (isset($_POST['_form_relations']) ) {
			$relations = explode(",", $_POST['_form_relations']);
		} else {
			$relations = [];
		}
		if ($model->loadAll(Yii::$app->request->post(), $relations) ) {
			if( $this->parent_model) {
				$model->setAttribute( $model->getRelatedFieldForModel($this->parent_model), $this->parent_model->getPrimaryKey());
			}
			$model->setIsNewRecord(true);
			foreach ($model->primaryKey() as $primary_key) {
				$model->$primary_key = null;
			}
			if( $this->doSave($model) ) {
				if( $this->afterSave('duplicate', $model) ) {
					$this->showFlash('create', $model);
					return $this->whereTogoNow('duplicate', $model);
				}
			}
		}
		return $this->render('saveAsNew', [
			'model' => $model,
			'parent' => $this->parent_model,
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
		if( $this->accessOnlyOwner ) {
			if( !$model->IAmOwner() ) {
				throw new \yii\web\ForbiddenHttpException(
					$model->t('churros', "You can't update {esta} {title} because you are not the author"));
			}
		}

		if (isset($_POST['_form_relations']) ) {
			$relations = explode(",", $_POST['_form_relations']);
		} else {
			$relations = [];
		}
		if ($model->loadAll(Yii::$app->request->post(), $relations) ) {
			if( $this->parent_model) {
				$model->setAttribute( $model->getRelatedFieldForModel($this->parent_model), $this->parent_model->getPrimaryKey());
			}
			if( $this->doSave($model) ) {
				if( $this->afterSave('update', $model) ) {
					$this->showFlash('update', $model);
					return $this->whereTogoNow('update', $model);
				}
			}
		}
		return $this->render('update', [
			'model' => $model,
			'parent' => $this->parent_model,
			'extraParams' => $this->changeActionParams($params,'update', $model)
		]);
	}

	protected function doSave($model)
	{
		$saved = false;
		$fileAttributes = $this->addFileInstances($model);
		if (count($fileAttributes) == 0) {
			$saved = $model->saveAll();
		} else {
			$transaction = $model->getDb()->beginTransaction();
			$saved = $model->validate();
			if ($saved ) {
				$saved = $this->saveFileInstances($model, $fileAttributes);
			}
			if ($saved) {
				$saved = $model->saveAll(false); // Do not validate again
			}
			if ($saved) {
				$transaction->commit();
			} else {
				$transaction->rollBack();
			}
		}
		return $saved;
	}

	/**
		* Deletes an existing model.
		* If deletion is successful, the browser will be redirected to the 'index' page.
		* @param integer $id
		* @return mixed
		* @todo delete uploaded files
		*/
	public function actionDelete($id)
	{
		$model = $this->findModel($id);
		if( $this->accessOnlyOwner ) {
			if( !$model->IAmOwner() ) {
				throw new \yii\web\ForbiddenHttpException(
					$model->t('churros', "You can't delete {esta} {title} because you are not the author"));
			}
		}
		try {
			$model->deleteWithRelated();
			if( $this->afterSave('delete', $model) ) {
				$this->showFlash('delete', $model);
				return $this->whereToGoNow('delete', $model);
			}
		} catch (\yii\db\IntegrityException $e ) {
			Yii::$app->session->addFlash('error',
				$model->t('churros', "{La} {title} <strong>{record_long}</strong> can't be deleted because it has related data"));
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
		if( $this->accessOnlyOwner ) {
			if( !$model->IAmOwner() ) {
				throw new \yii\web\ForbiddenHttpException(
					$model->t('churros', "You can't print to pdf {esta} {title} because you are not the author"));
			}
		}
		if( YII_DEBUG ) {
            Yii::$app->getModule('debug')->instance->allowedIPs = [];
        }
		// https://stackoverflow.com/a/54568044/8711400
		$content = $this->renderAjax('_pdf', [
			'model' => $model,
			'parent' => $this->parent_model,
			'extraParams' => $this->changeActionParams($params, 'view', $model)
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

	protected function getRelationsProviders($model) {
		return [];
	}

	// Método para eliminar una imagen de una galería
	public function actionRemoveImage($id, $field, $filename) {
		$model = $this->findModel($id);
		if ($model->$field == '') {
			throw new DataException($model->className() . "->$field is empty when removing an image");
		}
		$images = unserialize($model->$field);
		if (!is_array($images)) {
			throw new DataException($model->className() . "->$field is not an array");
		}
		if (isset($images[$filename])) {
			if ($this->unlinkImage($model, $filename)) {
				unset($images[$filename]);
				if( $images==[] ) {
					$model->$field = null;
				} else {
					$model->$field = serialize($images);
				}
				if (!$model->save()) {
					throw new SaveModelException($model);
				}
			} else {
				throw new DataException("Unable to delete " . $model->className() . "->$field[$filename]");
			}
		}
		return json_encode("Ok");
	}

	protected function addFileInstances($model)
	{
		$fileAttributes = $model->getFileAttributes();
		foreach ($fileAttributes as $key => $multiple) {
			$instances = UploadedFile::getInstances($model, $key);
			if (count($instances) == 0) {
				unset($fileAttributes[$key]);
				// Recupera el valor sobreescrito por el LoadAll del controller
// 				$model->$key = $model->getOldAttribute($key);
			} else {
// 				try {
// 					$attr_value = ($model->getOldAttribute($attr) != '' ? unserialize($model->getOldAttribute($attr)) : []);
// 				} catch( ErrorException $e) {
// 					$attr_value = $model->getOldAttribute($attr);
// // 					throw new ErrorException($e->getMessage() . "<br/>\n" . $model->getOldAttribute($attr));
// 				}
				foreach ($instances as $instance) {
					if ($instance->error != 0) {
						throw new HttpException(500, $this->fileUploadErrorMessage($model, $key, $file));
					}
				}
				if( $multiple == true ) {
					$model->$key = $instances;
				} else {
					$model->$key = $instances[0];
				}
			}
		}
		return $fileAttributes;
	}

	protected function saveFileInstances($model, $fileAttributes)
	{
		$saved = true;
		foreach ($fileAttributes as $attr => $multiple) {
			$model_attr = [];
			$instances = UploadedFile::getInstances($model, $attr);
			foreach ($instances as $file) {
				$filename = $this->getFileInstanceKey($file, $model, $attr);
				$saved = false;
				$model_attr[] = $filename;
				try {
					$saved = $file->saveAs(Yii::getAlias('@runtime/uploads/') . $filename);
					if (!$saved) {
						$model->addError($attr, "No se ha podido guardar el archivo $filename: " . posix_strerror($file->error));
					}
				} catch (yii\base\ErrorException $e) {
					$model->addError($attr, "No se ha podido guardar el archivo $filename: " . $e->getMessage());
				}
				if (!$saved) {
					break;
				}
			}
			$model->$attr = $multiple?serialize($model_attr):$model_attr[0];
		}
		return $saved;
	}

	private function getFileInstanceKey($uploadedfile, $model, $attr) {
		$filename = basename(str_replace('\\', '/', $model->className())) . "_$attr" . "_" . basename($uploadedfile->tempName) . "." . $uploadedfile->getExtension();
		return $filename;
	}

	private function unlinkImage($model, $filename) {
		$oldfilename = Yii::getAlias('@runtime/uploads/') . $filename;
		if (file_exists($oldfilename) && !@unlink($oldfilename)) {
			$model->addError($attr, "No se ha podido borrar el archivo $oldfilename" . posix_strerror($file->error));
			return false;
		} else {
			return true;
		}
	}

	private function fileUploadErrorMessage($model, $attr, $file) {
		$message = "Error uploading " . $model->className() . ".$attr: ";
		switch ($file->error) {
			case UPLOAD_ERR_OK:
				return "";
			case UPLOAD_ERR_INI_SIZE:
				$message .= "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message .= "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
				break;
			case UPLOAD_ERR_PARTIAL:
				$message .= "The uploaded file was only partially uploaded.";
				break;
			case UPLOAD_ERR_NO_FILE:
				$message .= "No file was uploaded.";
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message .= "Missing a temporary folder.";
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message .= "Failed to write file to disk.";
				break;
			case UPLOAD_ERR_EXTENSION:
				break;
		}
		return $message;
	}

	protected function formRelations()
	{
		return [];
	}

	public function parentRoute($action_if_no_parent = 'index')
	{
		if( $this->parent_model ) {
			$parent_route = $this->getRoutePrefix()
				. $this->parent_controller
				. '/' . $this->parent_model->getPrimaryKey();
		} else {
			$parent_route = Url::toRoute($action_if_no_parent);
		}
		return $parent_route;
	}

	protected function whereToGoNow($from, $model)
	{
		$returnTo = Yii::$app->request->getBodyParam('returnTo');
		if( $returnTo ) {
			return $this->redirect($returnTo);
		}
		$referrer = Yii::$app->request->post("_form_referrer");
		if ($from == 'create') {
			$referrer = null;
		}
		if ( $referrer ) {
			return $this->redirect($referrer);
		}
		if( $this->parent_model ) {
			return $this->redirect( $this->parentRoute() );
		} else {
			if ($from == 'update') {
				$redirect = ['view', 'id' => $model->getPrimaryKey()];
			} else if ($from == 'create') {
				if (Yii::$app->request->post('_and_create') == '1') {
					$redirect = ['create'];
				} else {
					$redirect = ["index"];
				}
			} else if ($from == 'duplicate') {
				$redirect = ["index"];
			} else if ($from == 'delete') {
				$redirect = ["index"];
			} else {
				throw new Exception("Where should I go now?");
			}
			return $this->redirect($redirect);
		}
	}

	public function genBreadCrumbs($action_id, $model, $parent)
	{
		$breadcrumbs = [];
		if( isset($parent) ) {
			$prefix = $this->getRoutePrefix() . $parent->controllerName(). '/';
			$breadcrumbs[] = [
				'label' => AppHelper::mb_ucfirst($parent->getModelInfo('title_plural')),
				'url' => [ $prefix . 'index']
			];
			$keys = $parent->getPrimaryKey(true);
			$keys[0] = $prefix . 'view';
			$breadcrumbs[] = [
				'label' => $parent->recordDesc('short', 25),
				'url' => $keys
			];
			// child
			$breadcrumbs[] = [
				'label' => AppHelper::mb_ucfirst($model->getModelInfo('title_plural')),
				'url' => $this->controllerRoute() . '/index'
			];
			switch( $action_id ) {
				case 'update':
					$breadcrumbs[] = [
						'label' => $model->recordDesc('short', 25),
						'url' => array_merge([$this->controllerRoute() . '/view'], $model->getPrimaryKey(true))
					];
					break;
				case 'index':
					break;
			}
		} else {
			$prefix = $this->getRoutePrefix();
			$breadcrumbs[] = [
				'label' =>  $model->getModelInfo('title_plural'),
				'url' => [ $this->id . '/index' ]
			];
			switch( $action_id ) {
				case 'update':
				case 'saveAsNew':
					$breadcrumbs[] = [
						'label' => $model->recordDesc('short', 20),
						'url' => array_merge([ $prefix . $this->id . '/view'], $model->getPrimaryKey(true))
					];
					break;
				case 'view':
					$breadcrumbs[] = $model->recordDesc('short', 20);
					break;
				case 'create':
					break;
				case 'index':
					break;
				default:
					throw new \Exception($action_id);
			}
		}
		return $breadcrumbs;
	}

	public function moduleRoute($action_id = null)
	{
		if( $this->parent_model ) {
			$parent_route = $this->parent_controller
				. '/' . $this->parent_model->getPrimaryKey()
				. '/'. $this->id;
			$action_id = (array) $action_id;
			if ($action_id[0] != '' && $action_id[0] == '/' ) {
				$action_id[0] = $parent_route . $action_id[0];
			} else {
				$action_id[0] = $parent_route . '/' . $action_id[0];
			}
			return Url::toRoute($action_id);
		} else if( $action_id !== null) {
			return Url::toRoute($action_id);
		} else {
			return Url::toRoute($this->id);
		}
	}

	public function getParentModel()
	{
		return $this->parent_model;
	}

	protected function getParentFromRequest()
	{
		if( $this->parent_model != null ) {
			return $this->parent_model;
		}
		$parent_id = intval(Yii::$app->request->get('parent_id', 0));
		if( $parent_id !== 0 ) {
			$this->parent_controller = Yii::$app->request->get('parent_controller');
			assert($this->parent_controller != '');
			$parent_model_name = 'app\\models\\'. AppHelper::camelCase($this->parent_controller);
			$parent_model = new $parent_model_name;
			$this->parent_model = $parent_model->findOne($parent_id);
			if ($this->parent_model == null) {
				throw new NotFoundHttpException($parent_model->t('churros',
					"El registro madre {title} de id '$parent_id' no existe"));
			}
		} else {
			return null;
		}
	}

	/**
	 * @param Model $parent The parent model (for detail_grids)
	 * @param Model $child The parent model (for detail_grids)
	 */
	public function controllerRoute($parent = null, $child= null)
	{
		if( $child == null && ($parent == null || $parent == $this->parent_model)) { // for normal grids
			$myroute = $this->getRoutePrefix() . $this->id;
			if( $this->parent_model ) {
				// myroute = /admin/model/11/update
				// prefix = /admin/parent/22/
				// result = /admin/parent/22/model/11/update
				$parent_route = $this->parent_controller. '/' . $this->parent_model->getPrimaryKey() . '/';
				$prefix = $this->getRoutePrefix() . $parent_route;
				// https://stackoverflow.com/questions/7475437/find-first-character-that-is-different-between-two-strings
				$pos_first_different = strspn($prefix ^ $myroute, "\0");
				// and go back to the /
				while( $pos_first_different >= 0 && $myroute[$pos_first_different] != '/' ) {
					--$pos_first_different;
				}
				if( $pos_first_different > 0 ) {
					++$pos_first_different;
				}
				$ret = $prefix . substr($myroute, $pos_first_different);
			} else {
				$ret = $myroute;
			}
		} else if( $child ) { // for detail_grids
			$ret = $this->getRoutePrefix($parent);
			$ret .= $parent->controllerName() . '/'
				. $parent->getPrimaryKey() . '/';
			$ret .= $child->controllerName();
		} else {
			return null;
		}
		return $ret;
	}

	protected function getRoutePrefix($parent_model = null)
	{
		if( $parent_model == null ) {
			$parent_model = $this->parent_model;
			$parent_controller = $this->parent_controller;
		} else {
			$parent_controller = $parent_model->controllerName();
		}
		$route = $this->id;
		$route_pos = false;
		$request_url = '/' . Yii::$app->request->getPathInfo();
		if( $parent_model ) {
			$parent_route = $parent_controller . '/' . $parent_model->getPrimaryKey();
			$route_pos = strpos($request_url, $parent_route . "/" . $route);
			if( $route_pos === false ) {
				$route_pos = strpos($request_url, $parent_route . $route);
			}
		}
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
		$model = $this->findModel($id);
		if( $this->accessOnlyOwner ) {
			if( !$model->IAmOwner() ) {
				throw new \yii\web\ForbiddenHttpException(
					$model->t('churros', "Accesd denied to {esta} {title} because you are not the author"));
			}
		}
		return json_encode($model->getAttributes());
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
			$link_to_me = Url::to(array_merge([$this->parentRoute('view')], $pk));
		} else {
			$link_to_me = $this->parentRoute('view') . "/$pk";
		}
		switch( $action_id ) {
		case 'update':
			Yii::$app->session->addFlash('success',
				strtr($model->t('churros', $this->getSuccessMessage('update')),
					['{model_link}' => $link_to_me]));
			break;
		case 'create':
			Yii::$app->session->addFlash('success',
				strtr($model->t('churros', $this->getSuccessMessage('create')),
					['{model_link}' => $link_to_me]));
			break;
		case 'delete':
			Yii::$app->session->addFlash('success',
				$model->t('churros', $this->getSuccessMessage('delete')));
			break;
		case 'duplicate':
			Yii::$app->session->addFlash('success',
				strtr($model->t('churros', $this->getSuccessMessage('duplicate')),
					['{model_link}' => $link_to_me]));
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

}
