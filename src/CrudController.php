<?php

namespace santilin\churros;

use Yii;
use yii\filters\VerbFilter;
use yii\web\{ForbiddenHttpException};
use santilin\churros\helpers\{FormHelper};

/**
 * CrudController implements the CRUD actions for yii2 models
 */
abstract class CrudController extends CrudReadOnlyController
{

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

	/**
	 * Creates a new model.
	 * @return mixed
	 */
	public function actionCreate($id = null)
	{
		$params = array_merge($this->request->get(), $this->request->post());
		$this->model = $this->findFormModel($id, null, 'create', $params);
		$params['permissions'] = $this->resolvePermissions($params['permissions'] ?? []);
		if ($master_model = $this->getMasterModel()) {
			$master_model->linkDetails($this->model);
		}
		if ($this->model->loadAll($params, static::findRelationsInForm($params))) {
			if ($this->model->saveAll(true)) {
				if ($this->request->getIsAjax()) {
					return json_encode($this->model->getAttributes());
				}
				$this->addSuccessFlashes('create', $this->model);
				$this->addWarningFlashes($this->model);
				return $this->redirect($this->returnTo(null, 'create', $this->model));
			}
		}
		return $this->render('create', [
			'model' => $this->model,
			'viewForms' => [ [ '_form', null , null, [], [] ] ],
			'formParams' => $this->changeActionParams($params, 'create', $this->model)
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
		$this->model = $this->findFormModel($id, null, 'duplicate', $params);
		$params['permissions'] = $this->resolvePermissions($params['permissions'] ?? []);
		if ($this->model->loadAll($this->request->post(), static::findRelationsInForm($params))) {
			$this->model->setIsNewRecord(true);
			$saved_pks = $this->model->getPrimaryKey(true);
			if ($this->model->saveAll(true)) {
				if ($this->request->getIsAjax()) {
					Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
					return ['model' => $this->model->getAttributes(), 'success' => $this->model->getSuccesses()];
				}
				$this->addSuccessFlashes('duplicate', $this->model);
				$this->addWarningFlashes($this->model);
				return $this->redirect($this->returnTo(null, 'duplicate', $this->model));
			} else {
				$this->model->setAttributes($saved_pks);
			}
		}
		return $this->render('duplicate', [
			'model' => $this->model,
			'viewForms' => [ [ '_form', null, null, [], [] ] ],
			'formParams' => $this->changeActionParams($params, 'duplicate', $this->model)
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
		$this->model = $this->findFormModel($id, null, 'update', $params);
		$params['permissions'] = $this->resolvePermissions($params['permissions'] ?? []);
 		if ($this->model === null && FormHelper::hasPermission($params['permissions'], 'create')) {
			return $this->redirect(array_merge(['create'], $params));
		}
		if ($this->model->loadAll($params, static::findRelationsInForm($params))) {
			if ($this->model->saveAll(true)) {
				if ($this->request->getIsAjax()) {
					Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
					return ['model' => $this->model->getAttributes(), 'success' => $this->model->getSuccesses()];
				}
				$this->addSuccessFlashes('update', $this->model);
				$this->addWarningFlashes($this->model);
				return $this->redirect($this->returnTo(null, 'update', $this->model));
			}
		}
		return $this->render('update', [
			'model' => $this->model,
			'viewForms' => [ [ '_form', null, [], [] ] ],
			'formParams' => $this->changeActionParams($params, 'update', $this->model)
		]);
	}

	/**
	 * Deletes an existing model.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionDelete($id)
	{
		$this->model = $this->findModel($id);
		try {
			if ($this->model->deleteWithRelated()) {
				if ($this->request->getIsAjax()) {
					Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
					return ['model' => $this->model->getAttributes(), 'success' => $this->model->getSuccesses()];
				}
				$this->addSuccessFlashes('delete', $this->model);
				$this->addWarningFlashes($this->model);
				return $this->redirect($this->returnTo(null, 'delete', $this->model));
			} else {
				Yii::$app->session->addFlash('error', $this->model->t('churros', $this->getResultMessage('error_delete')));
				$this->addErrorFlashes($this->model);
			}
		} catch (\yii\db\IntegrityException $e) {
			$this->model->addError('delete', $this->model->t('churros',
				$this->getResultMessage('error_delete_integrity')));
			if (YII_ENV_DEV) {
				$this->model->addError('delete_integrity', $e->getMessage());
			}
		} catch (\yii\web\ForbiddenHttpException $e) {
			$this->model->addError('delete', $this->model->t('churros',
				$this->getResultMessage('error_delete')));
		}
		$this->addErrorFlashes($this->model);
		return $this->redirect($this->returnTo(null, 'delete_error', $this->model));
	}

}
