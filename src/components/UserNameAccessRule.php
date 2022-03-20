<?php

namespace santilin\churros\components;

use yii\filters\AccessRule;

class UserNameAccessRule extends AccessRule
{

    protected function matchRole($user)
	{
        $items = empty($this->roles) ? [] : $this->roles;

        if (empty($items)) {
            return true;
        }

        if ($user === false) {
            throw new InvalidConfigException('The user application component must be available to specify roles in AccessRule.');
        }
		return in_array($user->getIdentity()->username, $items);
	}

}

