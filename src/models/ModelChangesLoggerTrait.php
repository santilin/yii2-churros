<?php

namespace santilin\churros\models;

trait ModelChangesLoggerTrait
{
	/**
	 * Finds the relation name that links to the changed model
	 */
	private function _changedModelRelationName(): string
	{
		$nfield = $this->field??null;
		if (!$nfield) {
			$nfield = substr($this->value, 0, strpos($this->value, ':'));
		}
		$field = $this->getStaticFieldLabel($nfield);
		if (($pos=strpos($field, '.')) === FALSE) {
			return $field;
		} else {
			return substr($field,0,$pos);
		}
	}

	/**
	 * Directly gets the relation name of the changed model
	 */
	public function getChangedModelTitle(): string
	{
		$relation_name = $this->_changedModelRelationName();
		$relation = static::$relations[$relation_name];
		return $relation_name;
	}

	/**
	 * Relation that returns a relation query to the changed model
	 */
	public function getChangedModel()
	{
		$relation_name = mb_ucfirst($this->_changedModelRelationName());
		$getter = "get$relation_name";
		return call_user_func([$this, $getter]);
	}


	static public function extractChangesForNotifications(string $value)
	{
		$ret = [];
		if (preg_match_all('/(([0-9]+):([0-9]+):([0-9]+){#([^,]*)#}),*/', $value??'', $changes, PREG_SET_ORDER)) {
			$c = [];
			foreach ($changes as $change) {
				$c['id'] = $change[2];
				$c['field'] = $change[3];
				$c['subtype'] = $change[4];
				$c['value'] = $change[5];
				$ret[] = $c;
			}
		}
		return $ret;
	}


}
