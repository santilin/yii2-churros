<?php
/**
 * A group for a grid
 */

namespace santilin\churros\grid;

use yii\base\BaseObject;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

class GridGroup extends BaseObject
{
	const NO_GROUP_CHANGE = 0;
	const NEW_FOOTER_AND_HEADER = 1;
	const NEW_HEADER = 2;

	/**
	 * The parent grid
	 */
	public $grid;
	/**
	 * @var string The column we are grouping by
	 */
	public $column;

	public $header;

	public $footer;

	public $visible = true;

	public $value;

	public $group_change = false;


	public $format;
	public $header_format;
	public $footer_format;
	public $label;
	public $header_label;
	public $footer_label;

	public $level = 0;
	protected $got_value = false;
	protected $last_value = null, $current_value = null;
	protected $summary_values = [];

	public function getCurrentValue()
	{
		return $this->current_value;
	}
	public function updateGroup($model, $key, $index)
	{
		// have we've got the value on willUpdateGroup?
		if( !$this->got_value[$this->level] ) {
			if( $this->value instanceOf \Closure ) {
				$this->current_value = call_user_func($this->value, $model, $key, $index, $this->grid);
			} else {
				$this->current_value = ArrayHelper::getValue($model, $this->column);
			}
		} else {
			$this->got_value[$this->level] = false;
		}
		if( $this->last_value !== $this->current_value) {
			if( $this->last_value != null ) {
				$this->group_change = self::NEW_FOOTER_AND_HEADER;
			} else {
				$this->group_change = self::NEW_HEADER;
			}
		} else {
			$this->group_change = self::NO_GROUP_CHANGE;
		}
		$this->last_value = $this->current_value;
		$this->updateSummaries();
		return $this->group_change;
	}

	public function willUpdateGroup($model, $key, $index)
	{
		if( $this->value instanceOf \Closure ) {
			$this->current_value = call_user_func($this->value, $model, $key, $index, $this->grid);
		} else {
			$this->current_value = ArrayHelper::getValue($model, $this->column);
		}
		$this->got_value[$this->level] = true;
		if( $this->last_value !== $this->current_value) {
			if( $this->last_value != null ) {
				return true;
			}
		}
		return false;
	}

	public function getHeaderContent($model, $key, $index)
	{
		$hc = isset($this->header['content']) ? $this->header['content'] : $this->header;
		if( $hc instanceOf \Closure ) {
			return call_user_func($hc, $model, $key, $index, $this);
		} else {
			$format = isset($this->header_format) ? $this->header_format : isset($this->format) ? $this->format : 'raw';
			switch($format) {
				case 'string':
				case 'integer':
					$content = Yii::$app->formatter->format($this->current_value, $format);
					break;
				case 'raw':
					$content = $this->current_value;
					break;
				default:
					$content = strtr( $format, [
						'{group_value}' => $this->current_value
					]);
					break;
			}
			return Html::tag('div', $content, [
				'class' => 'grid-group-head-' . strval($this->level)
			]);
		}
	}

	public function getFooterContent($model, $key, $index)
	{
		$fc = isset($this->footer['content']) ? $this->footer['content'] : $this->footer;
		if( $fc instanceOf \Closure ) {
			return call_user_func($hc, $model, $key, $index, $this);
		} else {
			$format = isset($this->footer_format) ? $this->footer_format : isset($this->format) ? $this->format : 'raw';
			switch($format) {
				case 'string':
				case 'integer':
					$content = Yii::$app->formatter->format($this->last_value, $format);
					break;
				case 'raw':
					$content = $this->last_value;
					break;
				default:
					$content = strtr( $format, [
						'{group_value}' => $this->last_value
					]);
					break;
			}
			return Html::tag('div', $content, [
				'class' => 'grid-group-head-' . strval($this->level)
			]);
		}
	}

	public function resetSummaries($summary_columns)
	{
		foreach( $summary_columns as $kc => $summary) {
			$this->summary_values[$kc] = null;
		}
	}

	public function updateSummaries($summary_columns, $row_values)
	{
		foreach( $summary_columns as $kc => $summary) {
			switch( $summary ) {
				$this->summary_values[$kc] += $row_values[$kc];
			}
		}
	}

}
