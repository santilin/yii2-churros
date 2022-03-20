<?php

namespace santilin\churros\components;

use yii\filters\AccessRule;
use yii\base\InvalidConfigException;

class CrudRbacAccessRule extends AccessRule
{

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

		$actions = array_merge($this->actions??[], ['index','create','update','delete','view','pdf','duplicate','autocomplete','remove-image']);
		switch( $action->id ) {
			case 'index':
			case 'view':
			case 'pdf':
			case 'autocomplete':
				$perm = ucfirst($action->controller->id) . "_View";
				break;
			case 'update':
				$perm = ucfirst($action->controller->id) . "_Edit";
				break;
			case 'delete':
			case 'remove-image':
				$perm = ucfirst($action->controller->id) . "_Dele";
				break;
			case 'create':
			case 'duplicate':
				$perm = ucfirst($action->controller->id) . "_Crea";
				break;
			default:
				$perm = ucfirst($action->controller->id) . "_" . ucfirst($action);
				break;
		}
		if( $user->can($perm) ) {
			if( $action->controller->hasProperty('accessOnlyOwner') ) {
				$action->controller->accessOnlyOwner = false;
			}
			return true;
		} else {
			$perm .= "_Own";
			if( $user->can($perm) ) {
				if( $action->controller->hasProperty('accessOnlyOwner') ) {
					$action->controller->accessOnlyOwner = true;
				}
				return true;
			} else {
				return false;
			}
		}
    }

}


