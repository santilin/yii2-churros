<?php

namespace santilin\churros\models;

use santilin\churros\helpers\AppHelper;
use santilin\churros\models\ModelChangesEvent;
use yii\db\ActiveRecord;

/**
 * A model that logs its creation, updates and deletion to a log model
 *
 * The using model must define these properties and methods:
 * @var string _log_model_changes_relation The name of the relation that links the using model with the log model
 * @todo
 * 	Interface:
 * 		function _log_changed_at_callback()
 * 		function _log_changed_by_callback()
 */
trait ModelChangesLoggableTrait
{
	// As of php8.1 there is not support for trait constants
	// These constants must be in sync with the `subtype` field of the using class
	public static $V_TYPE_CREATE = 1;
	public static $V_TYPE_UPDATE = 2;
	public static $V_TYPE_DELETE = 3;
	public static $V_SUBTYPE_CHANGE = 1;
	public static $V_SUBTYPE_EMPTY = 2;
	public static $V_SUBTYPE_CHANGECASE = 3;
	public static $V_SUBTYPE_CHANGESPACES = 4;
	public static $V_SUBTYPE_UNEMPTY = 5;
	public static $V_SUBTYPE_SETTRUE = 6;
	public static $V_SUBTYPE_SETFALSE = 7;
	public static $V_SUBTYPE_LINK = 8;
	public static $V_SUBTYPE_UNLINK = 9;

	/**
	 * Whether to log changes upon save or deleted
	 */
	protected $_model_changes_log = false;

	/**
	 * Whether to send notifications when a change is logged via ModelChangesEvent
	 */
	protected $_model_changes_notifications = false;

	///@todo
	protected $_model_changes_soft_delete;

	public function enableModelChangesLog(bool $enabled = true)
	{
		if ($this->_model_changes_log !== $enabled) {
			if (true === ($this->_model_changes_log = $enabled)) {
				$this->on(self::EVENT_AFTER_INSERT, [$this, 'handleModelChanges']);
				$this->on(self::EVENT_AFTER_UPDATE, [$this, 'handleModelChanges']);
				$this->on(self::EVENT_AFTER_DELETE, [$this, 'handleModelChanges']);
			} else {
				$this->off(self::EVENT_AFTER_INSERT);
				$this->off(self::EVENT_AFTER_UPDATE);
				$this->off(self::EVENT_AFTER_DELETE);
			}
		}
	}

	public function enableModelChangesNotifications(bool $enabled = true)
	{
		if ($this->_model_changes_notifications !== $enabled) {
			if ($this->_model_changes_notifications = $enabled) {
				$this->on(ModelChangesEvent::EVENT_CHANGES_SAVED, [$this, 'sendModelChangesNotification']);
			} else {
				$this->off(ModelChangesEvent::EVENT_CHANGES_SAVED);
			}
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
			$_log_model_changes_relation_info = static::$relations[static::$_log_model_changes_relation];
			$model_change_class = $_log_model_changes_relation_info['modelClass'];
			$record_id = strval(count($this->primaryKey())==1 ? $this->getPrimaryKey() : json_encode($this->getPrimaryKey(true)));
			if ($event->name === self::EVENT_AFTER_INSERT) {
				foreach ($this->getAttributes() as $fld => $current_value) {
					if (false !== ($nfield = $model_change_class::findChangeableFieldIndex($model_name, $fld))) {
						if ($current_value === null || trim($current_value) === '') {
							continue;
						}
						$model_change = new $model_change_class();
						$this->internalSaveModelChangeRecord($model_change, $record_id,
							$model_change_class::V_TYPE_CREATE, $nfield, $current_value);
						$must_trigger = true;
					}
				}
			} else if ($event->name === self::EVENT_AFTER_UPDATE) {
				foreach ($event->changedAttributes as $fld => $old_value) {
					if (($current_value = $this->$fld) == $old_value) { /// @todo use cast and then ===
						continue;
					}
					if (false !== ($nfield = $model_change_class::findChangeableFieldIndex($model_name, $fld))) {
						$model_change = new $model_change_class();
						$this->internalSaveModelChangeRecord($model_change, $record_id,
							$model_change_class::V_TYPE_UPDATE, $nfield, $old_value, $current_value);
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

	private function internalSaveModelChangeRecord(
		\yii\db\ActiveRecord $model_change,
		string $record_id,
		int $type,
		int $nfield,
		mixed $old_value,
		mixed $current_value = null,
	): void
	{
		if (!$model_change->getIsNewRecord()) {
			$model_change->resetPrimaryKeys();
			$model_change->setIsNewRecord(true);
		}
		$model_change->record_id = $record_id;
		$model_change->field = $nfield;
		$model_change->type = $type;
		if ($type === $model_change::V_TYPE_CREATE) {
			$model_change->value = $old_value;
			$model_change->changed_at = $this->created_at ?? new \yii\db\Expression("NOW()");
			if (YII_ENV_TEST && !($this->created_by ?? false)) {
				$model_change->changed_by = 1;
			} else {
				$model_change->changed_by = $this->created_by ?? \Yii::$app->user?->identity?->id;
			}
			if (self::$isJunctionModel) {
				$model_change->subtype = $model_change::V_SUBTYPE_LINK;
			}
		} else {
			$model_change->value = $old_value;
			$model_change->changed_at = new \yii\db\Expression("NOW()");
			if (\Yii::$app instanceof \yii\web\Application) {
				$model_change->changed_by = \Yii::$app->user?->identity?->id;
			} else {
				$model_change->changed_by = \Yii::$app->params['user_identity_id'] ?? null;
			}
			if (method_exists($this, 'modelChangesComment')) {
				$model_change->comments = $this->modelChangesComment();
			}
			if (is_bool($current_value)) {
				if ($current_value) {
					$model_change->subtype = $model_change::V_SUBTYPE_SETTRUE;
					$model_change->value = 0;
				} else {
					$model_change->value = 1;
					$model_change->subtype = $model_change::V_SUBTYPE_SETFALSE;
				}
			} else if ($current_value == '') {
				$model_change->subtype = $model_change::V_SUBTYPE_EMPTY;
			} else if ($current_value != '' && $old_value == '') {
				$model_change->subtype = $model_change::V_SUBTYPE_UNEMPTY;
			} else if (AppHelper::mb_strcasecmp($current_value, $old_value??'', 'UTF-8') == 0) {
				$model_change->subtype = $model_change::V_SUBTYPE_CHANGECASE;
			} else if (str_replace([' ',"\t","\n","\r"], '', $old_value) ==
				str_replace([' ',"\t","\n","\r"], '', $current_value)) {
				$model_change->subtype = $model_change::V_SUBTYPE_CHANGESPACES;
			} else {
				$model_change->subtype = $model_change::V_SUBTYPE_CHANGE;
			}
		}
		$model_change->saveOrFail();
	}

	/**
	 * @param ?int $subtype can be null on grouped changes
	 */
	public function formatModelChange(int $type, ?int $subtype, string|int $changed_field, string $changed_field_name, mixed $new_value, mixed $old_value): string
	{
		if ($type === self::$V_TYPE_CREATE) {
			switch ($subtype) {
				case self::$V_SUBTYPE_LINK:
					return  "añadió {la} {title} `" . $new_value . "`";
				case self::$V_SUBTYPE_CHANGE:
					$changed_label = $this->getAttributeLabel($changed_field_name);
					return "<mark>`$changed_label`</mark>: `$new_value`";
				case 0: // grouped
				default:
					return  "creó {la} {title} `" . $new_value . "`";
			}
		} else if ($type === self::$V_TYPE_UPDATE) {
			$changed_label = $this->getAttributeLabel($changed_field_name);
			switch ($subtype) {
				case self::$V_SUBTYPE_EMPTY:
					return  " vació `" . $changed_label . '`';
				case self::$V_SUBTYPE_CHANGECASE:
					return  " retocó las mayúsculas de `" . $changed_label . '`';
				case self::$V_SUBTYPE_CHANGESPACES:
					return  " retocó los espacios de `" . $changed_label . '`';
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
	}

	public function sendModelChangesNotification(ModelChangesEvent $e)
	{
		$changes_record = $e->getChangesRecord();
		$changes_record->sendModelChangesNotification();
	}

	public function createJunctionChangeLogs(ActiveRecord $main_model_change, array $relation): bool
	{
		$relations = static::$relations ?? [];
		foreach ($relations as $relName => $relDef) {
			if ($relDef['type'] !== 'HasOne') {
				continue;
			}
			try {
				$related = $this->$relName;
			} catch (\Exception $e) {
				continue;
			}
			if (!$related instanceof ActiveRecord) {
				continue;
			}
			$trait = \santilin\churros\models\ModelChangesLoggableTrait::class;
			if (in_array($trait, class_uses($related), true)) {
				$record_id = strval(count($related->primaryKey())==1 ? $related->getPrimaryKey() : json_encode($related->getPrimaryKey(true)));
				if (empty($record_id)) {
					continue;
				}
				$changes_log_model = new $relation['modelClass']();
				$changes_log_model->field = $main_model_change->field;
				$changes_log_model->changed_at = $main_model_change->changed_at;
				$changes_log_model->changed_by = $main_model_change->changed_by;
				$changes_log_model->record_id = $record_id;
				$changes_log_model->type = $main_model_change->type;
				$changes_log_model->subtype = $main_model_change->subtype;
				$changes_log_model->value = $main_model_change->value;
				$changes_log_model->comments = $main_model_change->comments;
				$ret = $changes_log_model->save();
				if (!$ret) {
					$this->addErrorsFrom($changes_log_model, 'changes_logger');
					return false;
				}
			}
		}

		return true;
	}
}
