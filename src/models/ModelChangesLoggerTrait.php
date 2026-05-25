<?php

namespace santilin\churros\models;

use santilin\churros\helpers\AppHelper;

trait ModelChangesLoggerTrait
{
	/**
	 * Finds the relation name that links to the changed model
	 */
	private function _changedModelRelationName(): string
	{
		$nfield = $this->field;
		if ($nfield === null || $nfield === false || $nfield === '') {
			$value = $this->value ?? '';
			if (str_starts_with($value, '[')) {
				$parsed = json_decode($value, true);
				$nfield = (is_array($parsed) && isset($parsed[0][0])) ? $parsed[0][0] : null;
			} else {
				$nfield = substr($value, 0, strpos($value, ':') ?: 0);
			}
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

	public function findNextValue($changed_model, string $changed_field)
	{
		if (strpos($changed_field, '.') === FALSE) {
			return $this->value;
		}
		$next_model = static::find()->select('value')
			->andWhere(['>', 'id', $this->id])
			->andWhere(['field' => $this->field])
			->andWhere(['record_id' => $this->record_id])
			->orderBy('id')
			->one();
		$field = AppHelper::lastWord($changed_field, '.');
		if ($next_model) {
			return $next_model->value;
		} else {
			return $changed_model->$field;
		}
	}

	static public function extractChangesForNotifications(string $value)
	{
		$ret = [];
		if (preg_match_all('/(?:(\d+):(\d+):(\d+)\{#([^#]*?)#\},?)+/', $value??'', $changes, PREG_SET_ORDER)) {
			$c = [];
			foreach ($changes as $change) {
				$c['id'] = $change[1];
				$c['field'] = $change[2];
				$c['subtype'] = $change[3];
				$c['value'] = $change[4];
				$ret[] = $c;
			}
		}
		return $ret;
	}

	public function commentsAddon(): string
	{
		$ret = $this->comments ?? '';
		if (\Yii::$app->controller !== null) {
			$ret .= \yii\helpers\Html::a('Edit', [ $this->getModelInfo('controller_name') . '/update/', 'id' => $this->id]);
		}
		return $ret;
	}

}
