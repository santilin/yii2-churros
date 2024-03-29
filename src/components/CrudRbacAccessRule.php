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
        if( !empty($this->actions) ) {
			if( !in_array($action->id, $this->actions) ) {
				return false;
			}
		}
		switch( $action->id ) {
			case 'index':
			case 'autocomplete':
				$perm = AppHelper::camelCase($action->controller->id) . ".index";
				break;
			case 'view':
			case 'pdf':
			case 'print':
				$perm = AppHelper::camelCase($action->controller->id) . ".view";
				break;
			case 'update':
				$perm = AppHelper::camelCase($action->controller->id) . ".edit";
				break;
			case 'delete':
			case 'remove-image':
				$perm = AppHelper::camelCase($action->controller->id) . ".delete";
				break;
			case 'duplicate':
				if( $user->can(AppHelper::camelCase($action->controller->id) . ".create") ) {
					$perm = AppHelper::camelCase($action->controller->id) . ".view";
				} else {
					return false;
				}
				break;
			case 'create':
				$perm = AppHelper::camelCase($action->controller->id) . ".create";
				break;
			default:
				$perm = AppHelper::camelCase($action->controller->id) . "." . ucfirst($action->id);
				break;
		}
		if( $this->module ) {
			$perm = $this->module . ".$perm";
		}
		if( $user->can("$perm.own") ) {
			if( $action->controller->hasProperty('accessOnlyMine') ) {
				$action->controller->accessOnlyMine = true;
			}
			return true;
		}
		if( $user->can($perm) ) {
			if( $action->controller->hasProperty('accessOnlyMine') ) {
				$action->controller->accessOnlyMine = false;
			}
			return true;
		}
		return false;
    }

} // class


