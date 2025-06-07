<?php

namespace santilin\churros\components;
use santilin\churros\helpers\AppHelper;

use yii\filters\AccessRule;
use yii\base\InvalidConfigException;

class CrudRbacAccessRule extends AccessRule
{
	public $module = null;

	public function allows($action, $user, $request)
	{
		if ($this->matchCrudAction($action, $user)
			&& $this->matchRole($user)
			&& $this->matchIP($request->getUserIP())
			&& $this->matchVerb($request->getMethod())
			&& $this->matchController($action->controller)
			&& $this->matchCustom($action)
		) {
			return $this->allow ? true : false;
		}

		return null;
	}

    protected function matchCrudAction($action, $user)
    {
		if ($user === false) {
            throw new InvalidConfigException('The user application component must be available to specify roles in AccessRule.');
        }
		$action_id = AppHelper::modelize($action->id, true); //nochangefirst
        if (!empty($this->actions)) {
			if (!in_array($action_id, $this->actions)) {
				if (!in_array(str_replace('-','_',$action->id), $this->actions)) {
					return false;
				} else {
					$action_id = str_replace('-','_',$action->id);
				}
			}
		}
		if (method_exists($action->controller, 'modelName')) {
			$model_name = $action->controller->modelName();
			switch (strtolower($action_id)) {
				case 'index':
				case 'autocomplete':
					$perm = "{$model_name}.index";
					break;
				case 'view':
				case 'pdf':
				case 'print':
					$perm = "{$model_name}.view";
					break;
				case 'update':
				case 'remove-image':
					$perm = "{$model_name}.update";
					break;
				case 'delete':
					$perm = "{$model_name}.delete";
					break;
				case 'duplicate':
				case 'create':
					$perm = "{$model_name}.create";
					break;
				default:
					$perm = "{$model_name}." . $action_id;
					break;
			}
		} else {
			$perm = $action_id;
		}
		if ($this->module ) {
			$perm = $this->module . ".$perm";
		}
		return $user->can($perm);
    }

} // class


