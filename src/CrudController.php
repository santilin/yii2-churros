<?php

namespace santilin\churros;

use Yii;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;
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

	/**
	 * An array of extra params to pass to the views
	 **/
	public function extraParams($action, $model)
	{
		return null;
	}

	public function behaviors() {
		return [
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'delete' => ['post'],
					'logout' => ['post'],
				],
			],
			'access' => [
				'class' => AccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'actions' => ArrayHelper::merge(['index', 'view', 'create', 'update', 'delete', 'pdf', 'duplicate', 'remove-image'], $this->allowedActions),
						'roles' => ['@']
					],
					[
						'allow' => false
					]
				]
			]
		];
	}

	public function beforeAction($action)
	{
		if (!parent::beforeAction($action)) {
			return false;
		}
		$this->getParentFromRequest();
		return true;
	}

	/**
		* Lists all models.
		* @return mixed
		*/
	public function actionIndex()
	{
		$searchModel = $this->createSearchModel();
		$params = Yii::$app->request->queryParams;
		if( $this->parent_model ) {
			$params[$searchModel->formName()][$searchModel->getRelatedFieldForModel($this->parent_model)]
				= $this->parent_model->getPrimaryKey();
		}

		return $this->render('index', [
			'searchModel' => $searchModel,
			'parent' => $this->parent_model,
			'gridParams' => $params,
			'extraParams' => $this->extraParams('index', $searchModel)
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
		return $this->render('view', [
			'model' => $model,
			'parent' => $this->parent_model,
			'relationsProviders' => $this->getRelationsProviders($model),
			'extraParams' => $this->extraParams('view', $model)
		]);
	}

	/**
		* Creates a new model.
		* If creation is successful, the browser will be redirected to the 'view' page.
		* @return mixed
		*/
	public function actionCreate()
	{
		$model = $this->findModel();
		if ($model->loadAll(Yii::$app->request->post()) ) {
			if( $this->parent_model) {
				$model->setAttribute( $model->getRelatedFieldForModel($this->parent_model), $this->parent_model->getPrimaryKey());
			}
			if( $this->doSave($model) ) {
				Yii::$app->session->setFlash('success',
						$model->t('churros', "{La} {title} <strong>{record}</strong> has been successfully created."));
				return $this->whereToGoNow('create', $model);
			}
		}
		$parent_id = Yii::$app->request->get('parent_id');
		$parent_controller = Yii::$app->request->get('parent_controller');
		return $this->render('create', [
			'model' => $model,
			'parent' => $this->parent_model,
			'extraParams' => $this->extraParams('index', $model)
		]);
	}

	/**
		* Creates a new model by another data,
		* so user don't need to input all field from scratch.
		* If creation is successful, the browser will be redirected to the 'view' page.
		*
		* @param mixed $id
		* @return mixed
		*/
	public function actionDuplicate($id)
	{
		if (Yii::$app->request->post('_asnew') != 0) {
			$id = Yii::$app->request->post('_asnew');
			$model = $this->findModel($id);
			$model->setDefaultValues(true); // duplicating
		} else {
			$model = $this->findModel($id);
		}

		if ($model->loadAll(Yii::$app->request->post()) ) {
			if( $this->parent_model) {
				$model->setAttribute( $model->getRelatedFieldForModel($this->parent_model), $this->parent_model->getPrimaryKey());
			}
			$model->setIsNewRecord(true);
			foreach ($model->primaryKey() as $primary_key) {
				$model->$primary_key = null;
			}
			if( $this->doSave($model) ) {
				Yii::$app->session->setFlash('success',
					$model->t('churros', "{La} {title} <strong>{record}</strong> has been successfully duplicated."));
				return $this->whereTogoNow('create', $model);
			}
		}
		return $this->render('saveAsNew', [
			'model' => $model,
			'parent' => $this->parent_model,
			'extraParams' => $this->extraParams('index', $model)
		]);
	}

	/**
	 * Updates an existing model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionUpdate($id) {
		$model = $this->findModel($id);

		if ($model->loadAll(Yii::$app->request->post()) ) {
			if( $this->parent_model) {
				$model->setAttribute( $model->getRelatedFieldForModel($this->parent_model), $this->parent_model->getPrimaryKey());
			}
			if( $this->doSave($model) ) {
				Yii::$app->session->setFlash('success',
					$model->t('churros', "{La} {title} <strong>{record}</strong> has been successfully updated."));
				return $this->whereTogoNow('update', $model);
			}
		}
		return $this->render('update', [
			'model' => $model,
			'parent' => $this->parent_model,
			'extraParams' => $this->extraParams('index', $model)
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
			$saved = $model->saveAll();
			if ($saved) {
				$saved = $this->saveFileInstances($model, $fileAttributes);
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
		try {
			$model->deleteWithRelated();
		} catch (\yii\db\IntegrityException $e ) {
			throw new \santilin\churros\DeleteModelException($model, $e);
		}
		Yii::$app->session->setFlash('success',
			$model->t('churros', "{La} {title} <strong>{record}</strong> has been successfully deleted."));
		return $this->whereToGoNow('delete', $model);
	}

	/**
	 * Export model information into PDF format.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionPdf($id) {
		$model = $this->findModel($id);
		if( YII_DEBUG ) {
            Yii::$app->getModule('debug')->instance->allowedIPs = [];
        }
		// https://stackoverflow.com/a/54568044/8711400
		$content = $this->renderAjax('_pdf', [
			'model' => $model,
		]);

		$pdf = new \kartik\mpdf\Pdf([
			'mode' => \kartik\mpdf\Pdf::MODE_CORE,
			'format' => \kartik\mpdf\Pdf::FORMAT_A4,
			'orientation' => \kartik\mpdf\Pdf::ORIENT_PORTRAIT,
			'destination' => \kartik\mpdf\Pdf::DEST_BROWSER,
			'content' => $content,
			'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
			'cssInline' => '.kv-heading-1{font-size:18px}',
			'options' => ['title' => \Yii::$app->name],
			'methods' => [
				'SetHeader' => [\Yii::$app->name],
				'SetFooter' => ['{PAGENO}'],
			]
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

	protected function addFileInstances($model) {
		$fileAttributes = $model->getFileAttributes();
		foreach ($fileAttributes as $key => $attr) {
			$instances = UploadedFile::getInstances($model, $attr);
			if (count($instances) == 0) {
				unset($fileAttributes[$key]);
				// Recupera el valor sobreescrito por el LoadAll del controller
				$model->$attr = $model->getOldAttribute($attr);
			} else {
				try {
				$attr_value = ($model->getOldAttribute($attr) != '' ? unserialize($model->getOldAttribute($attr)) : []);
				} catch( ErrorException $e) {
					throw new ErrorException($e->getMessage() . "<br/>\n" . $model->getOldAttribute($attr));
				}
				foreach ($instances as $file) {
					if ($file->error == 0) {
						$filename = $this->getFileInstanceKey($file, $model, $attr);
						$attr_value[$filename] = [$file->name, $file->size];
					} else {
						throw new HttpException(500, $this->fileUploadErrorMessage($model, $attr, $file));
					}
				}
				if ($attr_value == []) {
					$model->$attr = null;
				} else {
					$model->$attr = serialize($attr_value);
				}
			}
			if ($model->$attr == []) {
				$model->$attr = null;
			}
		}
		return $fileAttributes;
	}

	protected function saveFileInstances($model, $fileAttributes) {
		$saved = true;
		foreach ($fileAttributes as $attr) {
			$instances = UploadedFile::getInstances($model, $attr);
			foreach ($instances as $file) {
				$filename = $this->getFileInstanceKey($file, $model, $attr);
				$saved = false;
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

	protected function whereToGoNow($from, $model)
	{
		$returnTo = Yii::$app->request->get('returnTo');
		if( $returnTo ) {
			return $this->redirect($returnTo);
		}
		$referrer = Yii::$app->request->post("_form_referrer");
		if ($from == 'create') {
			if (Yii::$app->request->post('_and_create') == '1') {
				$referrer = null;
			}
		}
		if ( $referrer ) {
			return $this->redirect($referrer);
		}
		if ($from == 'update') {
			$redirect = ['view', 'id' => $model->getPrimaryKey()];
		} else if ($from == 'create') {
			if (Yii::$app->request->post('_and_create') == '1') {
				$redirect = ['create'];
			} else {
				$redirect = [ 'view', 'id' => $model->getPrimaryKey()];
			}
		} else if ($from == 'duplicate') {
			$redirect = ['view', 'id' => $model->getPrimaryKey()];
		} else if ($from == 'delete') {
			$redirect = ["index"];
		} else {
			throw new Exception("No sé donde volver");
		}
		if( $this->parent_model ) {
			return $this->redirect( $this->controllerRoute() );
		} else {
			return $this->redirect($redirect);
		}
	}

	public function genBreadCrumbs($action, $model, $parent)
	{
		assert( $model instanceof Model );
		assert( $parent == null || $parent instanceof Model );
		$breadcrumbs = [];
		if( isset($parent) ) {
			$prefix = $this->getRoutePrefix() . $parent->controllerName();
			$breadcrumbs[] = [
				'label' => AppHelper::mb_ucfirst($parent->getModelInfo('title_plural')),
				'url' => [ $prefix . '/index']
			];
			$keys = $parent->getPrimaryKey(true);
			$keys[0] = $prefix . '/view';
			$breadcrumbs[] = [
				'label' => $parent->recordDesc('short'),
				'url' => $keys
			];
			// child
			$breadcrumbs[] = [
				'label' => AppHelper::mb_ucfirst($model->getModelInfo('title_plural')),
				'url' => $this->controllerRoute() . '/index'
			];
			switch( $action ) {
				case 'update':
					$breadcrumbs[] = [
						'label' => $model->recordDesc('short'),
						'url' => [ $prefix . $this->id. '/view/' . strval($model->getPrimaryKey()) ] ];
					break;
				case 'index':
					break;
			}
		} else {
			$prefix = $this->getRoutePrefix();
			$breadcrumbs[] = [
				'label' => $model->t('churros', '{Title_plural}'),
				'url' => [ $this->id . '/index' ]
			];
			switch( $action ) {
				case 'update':
				case 'saveAsNew':
					$breadcrumbs[] = [
						'label' => $model->t('churros', '{record_short}'),
						'url' => [ $prefix . $this->id . '/view', 'id' => $model->getPrimaryKey() ]
					];
					break;
				case 'view':
				case 'create':
					break;
				case 'index':
					break;
				default:
					throw new \Exception($action);
			}
		}
		return $breadcrumbs;
	}

	public function moduleRoute($action = null)
	{
		if( $this->parent_model ) {
			$parent_route = $this->parent_controller
				. '/' . $this->parent_model->getPrimaryKey()
				. '/'. $this->id;
			$action = (array) $action;
			if ($action[0] != '' && $action[0] == '/' ) {
				$action[0] = $parent_route . $action[0];
			} else {
				$action[0] = $parent_route . '/' . $action[0];
			}
			return Url::toRoute($action);
		} else if( $action !== null) {
			return Url::toRoute($action);
		} else {
			return Url::toRoute($this->id);
		}
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
	 */
	public function controllerRoute($child = null)
	{
		if( $child == null) { // for normal grids
			$myroute = Url::toRoute('index');
			if( $this->parent_model ) {
				// myroute = /admin/model/11/update
				// prefix = /admin/parent/22/
				// result = /admin/parent/22/model/11/update
				$parent_route = $this->parent_controller. '/' . $this->parent_model->getPrimaryKey() . '/';
				$prefix = $this->getRoutePrefix() . $parent_route;
				// https://stackoverflow.com/questions/7475437/find-first-character-that-is-different-between-two-strings
				$pos_first_different = strspn($prefix ^ $myroute, "\0");
				$ret = $prefix . substr($myroute, $pos_first_different);
			} else {
				$ret = $myroute;
			}
			if( ($pos = strrpos($ret, '/index')) !== FALSE ) {
				$ret = substr($ret, 0, $pos);
			}
		} else { // for detail_grids
			$ret = '/' . Yii::$app->request->getPathInfo()
				. '/' .$child->controllerName();
		}
		return $ret;
	}

	protected function getRoutePrefix()
	{
		$route = $this->id;
		$route_pos = false;
		$request_url = '/' . Yii::$app->request->getPathInfo();
		if( $this->parent_model ) {
			$parent_route = $this->parent_controller . '/' . $this->parent_model->getPrimaryKey();
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

}
