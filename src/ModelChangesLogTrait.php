<?php

namespace santilin\churros;

use santilin\churros\helpers\AppHelper;

trait ModelChangesLogTrait
{
	protected $_model_changes_log = false;
	protected $_model_changes_soft_delete;

	/**
	 * Only enable loggin changes when needed
	 */
	public function enableChangesLog(bool $enabled)
	{
		$this->_model_changes_log = $enabled;
	}

	// Logs the changes after the model is saved or deleted
	public function handleModelChanges($event)
	{
		if (!$this->_model_changes_log) {
			return;
		}
		if ($event->name == self::EVENT_AFTER_DELETE) {
			// 			$pc = new participanteChange;
			// 			$pc->participantes_id = $this->id;
			// 			$pc->type = participanteChange::V_TYPE_DELETE;
			// 			$pc->saveOrFail();
		} else {
			$model_name = $event->sender->getModelInfo('model_name');
			$_model_changes_relation_info = static::$relations[$this->_model_changes_relation];
			$record_id = count($this->primaryKey())==1 ? $this->getPrimaryKey() : json_encode($this->getPrimaryKey(true));
			$pc = new $_model_changes_relation_info['modelClass'];
 			$left_field = AppHelper::lastWord($_model_changes_relation_info['right'], '.');
			if (get_class($this) == get_class($event->sender)) {
				$left_value = $this->getPrimaryKey();
			} else {
				$left_value = $event->sender->$left_field;
			}
			if ($event->name == self::EVENT_AFTER_INSERT) {
				$pc->$left_field = $left_value;
				$pc->field = null;
				$pc->changed_at = $this->created_at;
				$pc->changed_by = $this->created_by;
				$pc->type = $pc::V_TYPE_CREATE;
				$pc->value = $record_id;
				$pc->record_id = $this->recordDesc('short');
				$pc->saveOrFail();
			} else if ($event->name == self::EVENT_AFTER_UPDATE) {
				foreach ($event->changedAttributes as $fld => $old_value) {
					if ($nfield = $pc::findChangeableFieldIndex($model_name.'.'.$fld)) {
						if ($this->$fld == $old_value) {
							continue;
						}
						if (!$pc->getIsNewRecord()) {
							$pc->resetPrimaryKeys();
							$pc->setIsNewRecord(true);
						}
						$pc = new $_model_changes_relation_info['modelClass'];
						$pc->$left_field = $left_value;
						$pc->field = $nfield;
						$pc->record_id = strval($record_id);
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
					}
				}
			} else {
				throw new \yii\db\IntegrityException($event->name . ": invalid event name");
			}
		}
	}



}
