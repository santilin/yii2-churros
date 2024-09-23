<?php

namespace santilin\churros\models;

trait ModelChangesLoggerTrait
{
	public function changedRecord()
	{
		$nfield = $this->field??null;
		if (!$nfield) {
			$nfield = substr($this->value, 0, strpos($this->value, ':'));
		}
		$field = $this->getStaticFieldLabel($nfield);
		if (($pos=strpos($field, '.')) === FALSE) {
			$relation = $field;
		} else {
			$relation = substr($field,0,$pos);
		}
		return $this->$relation;
	}

}
