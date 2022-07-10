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
 * DetailaCrudController implements the CRUD actions for yii2 models acting as details in a master-detail relation
 */
class DetailCrudController extends CrudController
{
	protected $master_model = null;
	protected $master_controller = null;

	public function beforeAction($action)
	{
		if (!parent::beforeAction($action)) {
			return false;
		}
		$this->getMasterFromRequest();
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
		if( $this->master_model ) {
			$params[$name][$searchModel->getRelatedFieldForModel($this->master_model)]
				= $this->master_model->getPrimaryKey();
		}
		return $this->render('index', [
			'searchModel' => $searchModel,
			'indexParams' => $this->changeActionParams($params, 'index', $searchModel),
		]);
	}

	/**
	 * An array of extra params to pass to the views
	 **/
	protected function changeActionParams(array $queryParams, string $action_id, $model)
	{
		$queryParams['master'] = $this->master_model;
		return $queryParams;
	}


	protected function saveAll($model): bool
	{
		if( $this->master_model) {
			$model->setAttribute( $model->getRelatedFieldForModel($this->master_model), $this->master_model->getPrimaryKey());
		}
		return $model->saveAll();
	}

	public function actionRoute($action_id = null)
	{
		if( $this->master_model ) {
			$parent_route = $this->getRoutePrefix()
				. $this->master_controller
				. '/' . $this->master_model->getPrimaryKey();
			$action_id = (array) $action_id;
			if ($action_id[0] != '' && $action_id[0] == '/' ) {
				$action_id[0] = $parent_route . $action_id[0];
			} else {
				$action_id[0] = $parent_route . '/' . $action_id[0];
			}
			return Url::toRoute($action_id);
		} else {
			return Url::toRoute($action_id);
		}
	}

	/**
	 * @param Model $master The master model (for detail_grids)
	 * @param Model $child The child model (for detail_grids)
	 */
	public function controllerRoute($parent = null, $child= null): ?string
	{
		if( $child == null && ($parent == null || $parent == $this->master_model)) { // for normal grids
			$myroute = $this->getRoutePrefix() . $this->id;
			if( $this->master_model ) {
				// myroute = /admin/model/11/update
				// prefix = /admin/parent/22/
				// result = /admin/parent/22/model/11/update
				$parent_route = $this->master_controller. '/' . $this->master_model->getPrimaryKey() . '/';
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

	protected function getMasterFromRequest()
	{
		if( $this->master_model != null ) {
			return $this->master_model;
		}
		$parent_id = intval(Yii::$app->request->get('parent_id', 0));
		if( $parent_id !== 0 ) {
			$this->master_controller = Yii::$app->request->get('master_controller');
			assert($this->master_controller != '');
			$master_model_name = 'app\\models\\'. AppHelper::camelCase($this->master_controller);
			$master_model = new $master_model_name;
			$this->master_model = $master_model->findOne($parent_id);
			if ($this->master_model == null) {
				throw new NotFoundHttpException($master_model->t('churros',
					"The master record of {title} with '{id}' id does not exist",
					[ '{id}' => $parent_id]));
			}
		} else {
			return null;
		}
	}

}
