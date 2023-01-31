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
			$params[$searchModel->formName()][$searchModel->getRelatedFieldForModel($this->master_model)]
				= $this->master_model->getPrimaryKey();
		}
		return $this->render('index', [
			'searchModel' => $searchModel,
			'indexParams' => $this->changeActionParams($params, 'index', $searchModel),
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
		$model = $this->findFormModel(null);
		if( $this->master_model ) {
			$model->setAttribute( $model->getRelatedFieldForModel($this->master_model), $this->master_model->getPrimaryKey());
		}
		if (isset($_POST['_form_relations']) ) {
			$relations = explode(",", $_POST['_form_relations']);
		} else {
			$relations = [];
		}
		if ($model->loadAll(Yii::$app->request->post(), $relations) ) {
			if( $model->saveAll(true) ) {
				$this->addSuccessFlashes('create', $model);
				return $this->whereToGoNow('create', $model);
			}
		}
		return $this->render('create', [
			'model' => $model,
			'extraParams' => $this->changeActionParams($params, 'create', $model)
		]);
	}


	public function indexDetails($master, $params, $query = null)
	{
		$detail = $this->createSearchModel();
 		$params[$detail->formName()][$detail->getRelatedFieldForModel($master)]
 				= $master->getPrimaryKey();
		return $this->renderAjax('_detail_grid', [
			'dataProvider' => $detail->search($params),
			'searchModel' => $detail,
			'master' => $master,
			'indexParams' => $this->changeActionParams($params, 'index', $detail),
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


// 	protected function saveAll(string $context, $model): bool
// 	{
// 		if( $this->master_model && $model->getIsNewRecord() ) {
// 			$model->setAttribute( $model->getRelatedFieldForModel($this->master_model), $this->master_model->getPrimaryKey());
// 		}
// 		return $model->saveAll();
// 	}

	public function actionRoute($action_id = null, $model = null)
	{
		if( $this->master_model ) {
			$parent_route = $this->getRoutePrefix()
				. $this->id;
			if( $action_id ) {
				$action_id = (array) $action_id;
				if ($action_id[0] != '' && $action_id[0] == '/' ) {
					$action_id[0] = $parent_route . $action_id[0];
				} else {
					$action_id[0] = $parent_route . '/' . $action_id[0];
				}
				return Url::toRoute($action_id??'');
			} else {
				return $parent_route;
			}
		} else {
			return parent::actionRoute($action_id, $model);
		}
	}

	/**
	 * @param Model $master The master model (for detail_grids)
	 * @param Model $child The child model (for detail_grids)
	 */
	public function controllerRoute($master = null, $child = null): ?string
	{
		if( $master == null ) {
			$master = $this->master_model;
		}
		$master_route = $master->getModelInfo('controller_name');
		$ret = $this->getRoutePrefix($master_route);
		$ret .= $master->controllerName() . '/'
			. $master->getPrimaryKey() . '/';
		$ret .= $child->controllerName();
		return $ret;
	}

	protected function getMasterFromRequest()
	{
		if( $this->master_model != null ) {
			return $this->master_model;
		}
		$master_id = intval(Yii::$app->request->get('parent_id', 0));
		if( $master_id !== 0 ) {
			$this->master_controller = Yii::$app->request->get('parent_controller');
			assert($this->master_controller != '');
			$master_model_name = 'app\\models\\'. AppHelper::camelCase($this->master_controller);
			$master_model = new $master_model_name;
			$this->master_model = $master_model->findOne($master_id);
			if ($this->master_model == null) {
				throw new NotFoundHttpException($master_model->t('churros',
					"The master record of {title} with '{id}' id does not exist",
					[ '{id}' => $master_id]));
			}
		} else {
			return null;
		}
	}

	public function genBreadCrumbs($action_id, $model)
	{
		$breadcrumbs = [];
		if( $this->master_model) {
			$master = $this->master_model;
			$prefix = $this->getRoutePrefix() . $master->controllerName(). '/';
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
				'url' => $this->actionRoute('index')
			];
			switch( $action_id ) {
				case 'update':
					$breadcrumbs[] = [
						'label' => $model->recordDesc('short', 25),
						'url' => array_merge([$this->actionRoute('view')], $model->getPrimaryKey(true))
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
				case 'duplicate':
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

	public function getMasterModel()
	{
		return $this->master_model;
	}


}
