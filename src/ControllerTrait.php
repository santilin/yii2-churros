<?php

namespace santilin\churros;

use Yii;
use yii\helpers\{StringHelper,Url,Html};
use santilin\churros\helpers\FormHelper;

trait ControllerTrait
{

	public function getRoutePrefix($route = null, bool $add_slash = true): string
	{
		if( $route === null ) {
			$route = Url::toRoute($this->id);
		}
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
		$errors = [];
		foreach($model->getErrors() as $error_fld => $error_msgs) {
			foreach ($error_msgs as $error) {
				if( strpos($error, '{model_link}') !== FALSE ) {
					$link_to_model = $this->linkToModel($model);
					$errors[] = str_replace('{model_link}', $link_to_model, $error);
				} else {
					$errors[] = $error;
				}
			}
		}
		if (count($errors)) {
			Yii::$app->session->addFlash('error', implode("<br/>\n",$errors));
		}
		$warnings = [];
		foreach($model->getWarnings() as $warning_fld => $warning_msgs) {
			foreach ($warning_msgs as $warning) {
				if( strpos($warning, '{model_link}') !== FALSE ) {
					$link_to_model = $this->linkToModel($model);
					$warnings[] = str_replace('{model_link}', $link_to_model, $warning);
				} else {
					$warnings[] = $warning;
				}
			}
		}
		if (count($warnings)) {
			Yii::$app->session->addFlash('warning', implode("<br/>\n",$warnings));
		}
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


	public function joinMany2ManyModels(string $glue, array $models, string $record_format = 'long', bool $make_links = false): string
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
					$attrs[] = "<a href='$url'>" .  $model->recordDesc($record_format) . "</a>";
				} else {
					$attrs[] = $model->recordDesc($record_format);
				}
			}
		}
		return join($glue, $attrs);
	}

 	public function joinHasManyModels($parent, $models, $record_format = 'long',
									  $tag = 'ul', $tag_options = [])
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
				$url = Url::to(array_merge([$parent_route . '/'.  $model->controllerName()],  $model->getPrimaryKey(true)));
				$attrs[] = "<a href='$url'>" .  $model->recordDesc($record_format) . "</a>";
			}
		}
		switch ($tag) {
			case 'ul':
				return Html::tag($tag, '<li draggable="true">' . join('</li><li>', $attrs) . '</li>', $tag_options);
			case 'raw':
				return $attrs;
			default:
				return Html::tag($tag, join("</$tag><$tag>", $attrs), $tag_options);
		}
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


	public function getBaseRoute(): string
	{
		if ($this->module instanceof \yii\base\Application) {
			return '';
		} else if ($this->prefix) {
			return '/' . $this->prefix;
		} else {
			return '/' . $this->module->getUniqueId();
		}
	}

	/**
	 * Breadcrumbs for only one model, taking into account a prefix for:
	 * a) the parent on a hiearchy
	 * b) the parent in a master/detail
	 */
 	public function modelBreadCrumbs($model, string $action_id, string $prefix, array $permissions = [],
									 bool $last_one = false): array
	{
		$breadcrumbs = [];
		if ($prefix == '') {
			$prefix = $this->getBaseRoute() . '/';
		}
		$prefix .= $model->controllerName(). '/';
		$index_bc = [
			'label' => StringHelper::mb_ucfirst($model->getModelInfo('title_plural')),
		];
		if (FormHelper::hasPermission($permissions, 'index') && $action_id != 'index') {
			$index_bc['url'] = [ $prefix . 'index'];
		}
		$breadcrumbs[] = $index_bc;
		if (!$model->getIsNewRecord()) {
			$keys = $model->getPrimaryKey(true);
			$keys[0] = $prefix . 'view';
			$view_bc = [
				'label' => $model->recordDesc('short', 25)
			];
			if (!$last_one) {
				$view_bc['url'] = $keys;
			} else if ($action_id != 'view' && FormHelper::hasPermission($permissions, 'view')) {
				$view_bc['url'] = $keys;
			}
			$breadcrumbs[] = $view_bc;
		}
		return $breadcrumbs;
	}


} // trait
