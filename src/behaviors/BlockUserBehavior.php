<?php

namespace santilin\churros\behaviors;

use Da\User\Event\UserEvent;
use yii\base\{Behavior,Security};
use yii\db\BaseActiveRecord;

class BlockUserBehavior extends Behavior
{
	public $activeAttribute = 'active';
	public $userRelation = 'user';

	public function events()
	{
		return [
			BaseActiveRecord::EVENT_AFTER_UPDATE => 'updateUserStatus',
		];
	}

	public function updateUserStatus($event)
	{
		$model = $this->owner;
		if (!array_key_exists($this->activeAttribute, $event->changedAttributes)) {
			return; // 'activa' attribute didn't change, so we don't need to do anything
		}
		if ($model->{$this->activeAttribute} == $event->changedAttributes[$this->activeAttribute]) {
			// same value, different type
			return;
		}
		$user = $model->{$this->userRelation};
		if ($user) {
			if ($user->getIsBlocked() && $model->{$this->activeAttribute}) {
				$user->trigger(UserEvent::EVENT_BEFORE_UNBLOCK);
				$result = (bool)$user->updateAttributes(['blocked_at' => null]);
				$user->trigger(UserEvent::EVENT_AFTER_UNBLOCK);
			} else {
				$user->trigger(UserEvent::EVENT_BEFORE_BLOCK);
				$security = new Security;
				$result = (bool)$user->updateAttributes(
					['blocked_at' => time(), 'auth_key' => $security->generateRandomString()]
				);
				$user->trigger(UserEvent::EVENT_AFTER_BLOCK);
			}
		}
	}
}

