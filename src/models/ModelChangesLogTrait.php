<?php

namespace santilin\churros\models;

use santilin\churros\helpers\AppHelper;
use santilin\churros\models\ModelChangesEvent;

trait ModelChangesLogTrait
{
	protected $_model_changes_log = false;
	protected $_model_changes_soft_delete;

	/**
	 * Only enable loggin changes when needed
	 */
	public function enableChangesLog(bool $enabled = true)
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

	// Logs the changes after the model is saved or deleted
	public function handleModelChanges($event)
	{
		$must_trigger = false;
		if ($event->name == self::EVENT_AFTER_DELETE) {
			// 			$pc = new participanteChange;
			// 			$pc->participantes_id = $this->id;
			// 			$pc->type = participanteChange::V_TYPE_DELETE;
			// 			$pc->saveOrFail();
		} else {
			$model_name = get_class($event->sender);
			$_model_changes_relation_info = static::$relations[$this->_model_changes_relation];
			$record_id = strval(count($this->primaryKey())==1 ? $this->getPrimaryKey() : json_encode($this->getPrimaryKey(true)));
			$pc = new $_model_changes_relation_info['modelClass'];
			if ($event->name == self::EVENT_AFTER_INSERT) {
				$pc->record_id = $record_id;
				$pc->field = $pc::findChangeableFieldIndex($model_name);
				$pc->changed_at = $this->created_at;
				$pc->changed_by = $this->created_by;
				$pc->type = $pc::V_TYPE_CREATE;
				$pc->value = $this->recordDesc('short');
				$pc->saveOrFail();
				$must_trigger = true;
			} else if ($event->name == self::EVENT_AFTER_UPDATE) {
				foreach ($event->changedAttributes as $fld => $old_value) {
					if ($this->$fld == $old_value) {
						continue;
					}
					if ($nfield = $pc::findChangeableFieldIndex($model_name, $fld)) {
						if (!$pc->getIsNewRecord()) {
							$pc->resetPrimaryKeys();
							$pc->setIsNewRecord(true);
						}
						$pc = new $_model_changes_relation_info['modelClass'];
						$pc->record_id = $record_id;
						$pc->field = $nfield;
						$pc->value = $this->$fld;
						$pc->changed_by = $this->updated_by;
						$pc->changed_at = new \yii\db\Expression("NOW()");
						$pc->type = $pc::V_TYPE_UPDATE;
						if (is_bool($old_value)) {
							if ($old_value) {
								$pc->subtype = $pc::V_SUBTYPE_SETFALSE;
							} else {
								$pc->subtype = $pc::V_SUBTYPE_SETTRUE;
							}
						} else if ($old_value == '') {
							$pc->subtype = $pc::V_SUBTYPE_UNEMPTY;
						} else if ($pc->value == '') {
							$pc->subtype = $pc::V_SUBTYPE_EMPTY;
						} else if (AppHelper::mb_strcasecmp($pc->value, $old_value, 'UTF-8') == 0) {
							$pc->subtype = $pc::V_SUBTYPE_CHANGECASE;
						} else if (str_replace([' ',"\t","\n","\r"], '', $old_value) ==
							str_replace([' ',"\t","\n","\r"], '', $pc->value)) {
							$pc->subtype = $pc::V_SUBTYPE_CHANGESPACES;
						} else {
							$pc->subtype = $pc::V_SUBTYPE_CHANGE;
						}
						$pc->saveOrFail();
						$must_trigger = false;
					}
				}
			} else {
				throw new \yii\db\IntegrityException($event->name . ": invalid event name");
			}
			if ($must_trigger) {
				$this->trigger(ModelChangesEvent::EVENT_CHANGES_SAVED,
							   new ModelChangesEvent($this));
			}
		}
	}

}
