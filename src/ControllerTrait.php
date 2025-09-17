<?php

namespace santilin\churros;

use Yii;
use yii\helpers\{StringHelper,Url,Html};
use santilin\churros\helpers\FormHelper;

trait ControllerTrait
{
	static public function getModelName(bool $short = false): string
	{
		if ($short) {
			return StringHelper::lastWord($this->_model_name, '\\');
		} else {
			return $this->_model_name;
		}
	}

	static public function findRelationsInForm(array $form_params): array
	{
		if (!isset($form_params['_form_relations'])) {
			return [];
		}
		if (is_string($form_params['_form_relations'])) {
			try {
				$relations = json_decode($form_params['_form_relations'], JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
			} catch (JsonException $e) {
				$relations = explode(',', $form_params['_form_relations']);
			}
		} else {
			$relations = $form_params['_form_relations'];
		}
		$ret = [];
		foreach ($relations as $rk => $rn) {
			$rpos = strrpos($rk, '\\');
			if ($rpos !== FALSE) {
				$ret[substr($rk, $rpos+1)] = $rn;
			} else {
				$ret[$rk] = $rn;
			}
		}
		return $ret;
	}

	public function getRoutePrefix($route = null, bool $add_slash = true): string
	{
		if ($route === null) {
			$route = Url::toRoute($this->id);
		}
		$request_url = '/' . Yii::$app->request->getPathInfo();
		$route_pos = strpos($request_url, $route);
		$prefix = substr($request_url, 0, $route_pos);
		if ($add_slash) {
			if (substr($prefix, -1) != '/') {
				$prefix .= '/';
			}
		}
		return $prefix;
	}

	protected function getResultMessage(string $action_id): string
	{
		switch( $action_id) {
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

	protected function addSuccessFlashes(string $action_id, $model, ?string $success_message = null)
	{
		if ($success_message !== false) {
			if (!$success_message) {
				$success_messages = $model->getSuccessesSummary(true);
				if (count($success_messages) > 0) {
					$success_message = implode('<br/>', $success_messages);
				} else {
					$success_message = $model->t('churros', $this->getResultMessage($action_id));
				}
			} else {
				$success_message = $model->t('churros', $success_message);
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
				$errors[] = $error;
			}
		}
		if (count($errors)) {
			Yii::$app->session->addFlash('error', implode("<br/>\n",$errors));
		}
		$warnings = [];
		foreach($model->getWarnings() as $warning_fld => $warning_msgs) {
			foreach ($warning_msgs as $warning) {
				$warnings[] = $warning;
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
		if (!is_file($file)) {
			return null;
		} else {
			return $file;
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
		} else if (static::$_prefix) {
			return '/' . static::$_prefix;
		} else {
			return '/' . $this->module->getUniqueId();
		}
	}

	/**
	 * Breadcrumbs for only one model, taking into account a prefix for:
	 * a) the parent on a hiearchy
	 * b) the parent in a master/detail
	 */
 	public function modelBreadCrumbs($model, string $scenario, string $prefix, array $permissions = [],
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
		if (FormHelper::hasPermission($permissions, 'index')) {
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
			} else if ($scenario != 'view' && FormHelper::hasPermission($permissions, 'view')) {
				$view_bc['url'] = $keys;
			}
			$breadcrumbs[] = $view_bc;
		}
		return $breadcrumbs;
	}

	public function userPermissions(): array|bool
	{
		return $this->crudActions;
	}

	protected function resolvePermissions(...$arrays): array|bool
	{
		// If there is no user component, userPermissions must return crudActions
		$ret = array_intersect($this->crudActions, $this->userPermissions()?:[]);
		foreach ($arrays as $array) {
			if (!empty($array) && is_array($array)) {
				$ret = array_intersect($ret, $array);
			}
		}
		return $ret;
	}

	/**
	 * Override to adjust the _v param
	 */
	protected function changeSplitParams(array $params, $values, $field): array
	{
		return $params;
	}

	protected function extractAction(string $url): string
	{
		$url_parts = parse_url($url);
		if (!empty($url_parts['path'])) {
			$path_parts = explode('/', $url_parts['path']);
			return array_pop($path_parts);
		}
		return '';
	}

} // trait
