<?php

namespace santilin\churros\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;

class ArrayToJsonBehavior extends Behavior
{
	/**
	 * @var array List of attributes to be treated as JSON
	 */
	public $attributes = [];

	public function events()
	{
		return [
			ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
			ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
			ActiveRecord::EVENT_AFTER_REFRESH => 'afterFind',
		];
	}

	public function beforeSave($event)
	{
		foreach ($this->attributes as $attribute) {
			if (is_array($this->owner->$attribute) && $this->owner->$attribute !== ["0"]) {
				$this->owner->$attribute = json_encode($this->owner->$attribute);
			}
		}
	}

	public function afterFind($event)
	{
		foreach ($this->attributes as $attribute) {
			if (is_string($this->owner->$attribute)) {
				$this->owner->$attribute = json_decode($this->owner->$attribute, true);
			}
		}
	}
}
