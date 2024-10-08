<?php

namespace santilin\churros\models;

use santilin\churros\helpers\AppHelper;
use santilin\churros\models\ModelChangesEvent;

trait ModelChangesLoggableTrait
{
	public static $V_SUBTYPE_CHANGE = 1;
	public static $V_SUBTYPE_EMPTY = 2;
	public static $V_SUBTYPE_CHANGECASE = 3;
	public static $V_SUBTYPE_CHANGESPACES = 4;
	public static $V_SUBTYPE_UNEMPTY = 5;
	public static $V_SUBTYPE_SETTRUE = 6;
	public static $V_SUBTYPE_SETFALSE = 7;

	protected $_model_changes_log = false;
	protected $_model_changes_soft_delete;

	/**
	 * Only enable loggin changes when needed
	 */
	public function enableModelChangesLog(bool $enabled = true)
	{
		if ($this->_model_changes_log = $enabled) {
			$this->on(self::EVENT_AFTER_INSERT, [$this, 'handleModelChanges']);
			$this->on(self::EVENT_AFTER_UPDATE, [$this, 'handleModelChanges']);
			$this->on(self::EVENT_AFTER_DELETE, [$this, 'handleModelChanges']);
		} else {
			$this->off(self::EVENT_AFTER_INSERT);
			$this->off(self::EVENT_AFTER_UPDATE);
			$this->off(self::EVENT_AFTER_DELETE);
		}
	}

	protected $_model_changes_notifications = false;
	public function enableModelChangesNotifications(bool $enabled = true)
	{
		if ($this->_model_changes_notifications = $enabled) {
			$this->on(ModelChangesEvent::EVENT_CHANGES_SAVED, [$this, 'sendModelChangesNotification']);
		} else {
			$this->off(ModelChangesEvent::EVENT_CHANGES_SAVED);
		}
	}


	// Logs the changes after the model is saved or deleted
	public function handleModelChanges($event)
	{
		$must_trigger = false;
		if ($event->name == self::EVENT_AFTER_DELETE) {
			// 			$model_change = new participanteChange;
			// 			$model_change->participantes_id = $this->id;
			// 			$model_change->type = participanteChange::V_TYPE_DELETE;
			// 			$model_change->saveOrFail();
		} else {
			$model_name = $event->sender->getModelInfo('model_name');
			$_model_changes_relation_info = static::$relations[$this->_model_changes_relation];
			$record_id = strval(count($this->primaryKey())==1 ? $this->getPrimaryKey() : json_encode($this->getPrimaryKey(true)));
			$model_change = new $_model_changes_relation_info['modelClass'];
			if ($event->name == self::EVENT_AFTER_INSERT) {
				$model_change->record_id = $record_id;
				$model_change->field = $model_change::findChangeableFieldIndex($model_name);
				$model_change->changed_at = $this->created_at;
				$model_change->changed_by = $this->created_by;
				$model_change->type = $model_change::V_TYPE_CREATE;
				$model_change->value = $this->recordDesc('short');
				$model_change->saveOrFail();
				$must_trigger = true;
			} else if ($event->name == self::EVENT_AFTER_UPDATE) {
				foreach ($event->changedAttributes as $fld => $old_value) {
					if ($this->$fld == $old_value) {
						continue;
					}
					if ($nfield = $model_change::findChangeableFieldIndex($model_name, $fld)) {
						if (!$model_change->getIsNewRecord()) {
							$model_change->resetPrimaryKeys();
							$model_change->setIsNewRecord(true);
						}
						$model_change->record_id = $record_id;
						$model_change->field = $nfield;
						$model_change->value = $this->$fld;
						$model_change->changed_by = \Yii::$app->user->identity->id;
						$model_change->changed_at = new \yii\db\Expression("NOW()");
						$model_change->type = $model_change::V_TYPE_UPDATE;
						if (is_bool($old_value)) {
							if ($old_value) {
								$model_change->subtype = $model_change::V_SUBTYPE_SETFALSE;
							} else {
								$model_change->subtype = $model_change::V_SUBTYPE_SETTRUE;
							}
						} else if ($old_value == '') {
							$model_change->subtype = $model_change::V_SUBTYPE_UNEMPTY;
						} else if ($model_change->value == '') {
							$model_change->subtype = $model_change::V_SUBTYPE_EMPTY;
						} else if (AppHelper::mb_strcasecmp($model_change->value, $old_value, 'UTF-8') == 0) {
							$model_change->subtype = $model_change::V_SUBTYPE_CHANGECASE;
						} else if (str_replace([' ',"\t","\n","\r"], '', $old_value) ==
							str_replace([' ',"\t","\n","\r"], '', $model_change->value)) {
							$model_change->subtype = $model_change::V_SUBTYPE_CHANGESPACES;
						} else {
							$model_change->subtype = $model_change::V_SUBTYPE_CHANGE;
						}
						$model_change->saveOrFail();
						$must_trigger = true;
					}
				}
			} else {
				throw new \yii\db\IntegrityException($event->name . ": invalid event name");
			}
			if ($must_trigger) {
				$this->trigger(ModelChangesEvent::EVENT_CHANGES_SAVED,
							   new ModelChangesEvent($model_change));
			}
		}
	}

	public function formatModelChange(string $subtype, string $changed_field, string $changed_label, mixed $new_value): string
	{
		switch ($subtype) {
			case self::$V_SUBTYPE_EMPTY:
				return  " vació `" . $changed_label . '`';
			case self::$V_SUBTYPE_CHANGECASE:
				return  " retocó las mayúsculas de `" . $changed_label . '`';
			case self::$V_SUBTYPE_CHANGESPACES:
				return  " retocó los espacios de `" . $changed_label;
			case self::$V_SUBTYPE_UNEMPTY:
				return  " rellenó `" . $changed_label
				. '` con `' . strval($new_value) . '`';
			case self::$V_SUBTYPE_SETTRUE:
				return  " cambió `" . $changed_label . '` a verdadero';
			case self::$V_SUBTYPE_SETFALSE:
				return  " cambió `" . $changed_label . '` a falso';
			default:
				return  " cambió `" . $changed_label
				. '` a `' . strval($new_value) . '`';
		}
	}


	public function sendModelChangesNotification(ModelChangesEvent $e)
	{
		$changes_record = $e->getChangesRecord();
		$changes_record->sendModelChangesNotification();
	}

}
