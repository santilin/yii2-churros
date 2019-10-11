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
	protected $summaryValues = [];

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

	public function getHeaderContent($model, $key, $index, $tdoptions)
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
			return Html::tag('td',
				Html::tag('div', $content, [
					'class' => 'grid-group-head-' . strval($this->level)
					]),
				$tdoptions);
		}
	}

	public function getFooterContent($summary_columns, $model, $key, $index, $tdoptions)
	{
		$ret = '';
		$fc = isset($this->footer['content']) ? $this->footer['content'] : $this->footer;
		if( $fc === false ) {
			return '';
		} elseif( $fc instanceOf \Closure ) {
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
		}
		if( $fc === true /*'summary'*/ ) {
			$ret .= $this->getSummaryContent($summary_columns, $content);
		} else {
			$ret = Html::tag('td',
				Html::tag('div', $content, [
					'class' => 'grid-group-foot-' . strval($this->level)
					]),
				$tdoptions);
		}
		return $ret;
	}

	public function getSummaryContent($summary_columns, $content)
	{
		$colspan = 0;
		foreach( $this->grid->columns as $kc => $column ) {
			if( !isset($summary_columns[$kc]) ) {
				$colspan++;
			} else {
				break;
			}
		}
		$ret = Html::tag('td',
			Html::tag('div', "Totales ". $content, [
				'class' => 'grid-group-foot-' . strval($this->level) ]),
			['colspan' => $colspan]);
		$nc = 0;
		foreach( $this->grid->columns as $kc => $column ) {
			if( $nc++ < $colspan ) {
				continue;
			}
			if( isset($summary_columns[$kc]) ) {
				$ret .= Html::tag('td', Html::tag('div',
					$this->grid->formatter->format(
						$this->summaryValues[$kc], $column->format),
						GridView::fetchColumnOptions($column, $this->level)));
			} else {
				$ret .= Html::tag('td', '');
			}
		}
		return $ret;
	}

	public function resetSummaries($summary_columns)
	{
		foreach( $summary_columns as $kc => $summary) {
			switch( $summary ) {
			case 'f_sum':
			case 'f_count':
				$this->summaryValues[$kc] = 0;
				break;
			case 'f_avg':
				$this->summaryValues[$kc] = [0, 0];
				break;
			case 'f_max':
				$this->summaryValues[$kc] = null;
				break;
			case 'f_min':
				$this->summaryValues[$kc] = null;
				break;
			case 'f_concat':
			case 'f_distinct_concat':
				$this->summaryValues[$kc] = [];
				break;
			}
		}
	}

	public function updateSummaries($summary_columns, $row_values)
	{
		// same in GridView::updateSummaries
		foreach( $summary_columns as $key => $summary) {
			$kc = str_replace('.', '_', $key);
			switch( $summary ) {
			case 'f_sum':
				$this->summaryValues[$key] += $row_values[$kc];
				break;
			case 'f_count':
				$this->summaryValues[$key] ++;
				break;
			case 'f_avg':
				$this->summaryValues[$key][0] += $row_values[$kc];
				$this->summaryValues[$key][1] ++;
				break;
			case 'f_max:':
				if( $this->summaryValues[$key] < $row_values[$kc] ) {
					$this->summaryValues[$key] = $row_values[$kc];
				}
				break;
			case 'f_min:':
				if( $this->summaryValues[$key] > $row_values[$kc] ) {
					$this->summaryValues[$key] = $row_values[$kc];
				}
				break;
			case 'f_concat:':
				$this->summaryValues[$key][] = $row_values[$kc];
				break;
			case 'f_distinct_concat:':
				if (!in_array($row_values[$key], $this->summaryValues[$kc])) {
					$this->summaryValues[$key][] = $row_values[$kc];
				}
				break;
			}
		}
	}
}
