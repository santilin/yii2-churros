<?php

namespace santilin\churros\components;

use yii\base\InvalidConfigException;
use yii\filters\AccessRule;

class UserNameAccessRule extends AccessRule
{

    protected function matchRole($user)
	{
        $items = empty($this->roles) ? [] : $this->roles;
        if (empty($items)) {
            return true;
        }
        if( !$user->getIdentity() ) {
			return false;
        }
        // Gives an oportunity to not have rbac tables
		if( in_array($user->getIdentity()->username, $items) ) {
			return true;
		}
		if( parent::matchRole($user) ) {
			return true;
		}
	}

}

