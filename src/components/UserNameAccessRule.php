<?php

namespace santilin\churros\components;

use yii\base\InvalidConfigException;
use yii\filters\AccessRule;

class UserNameAccessRule extends AccessRule
{

    protected function matchRole($user)
	{
		if( parent::matchRole($user) ) {
			return true;
		}
        $items = empty($this->roles) ? [] : $this->roles;
        if (empty($items)) {
            return true;
        }
        if( !$user->getIdentity() ) {
			return false;
        }
		return in_array($user->getIdentity()->username, $items);
	}

}

