<?php
/**
 * A group for a grid
 */

namespace santilin\churros\grid;

use Yii;
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
	public function getLastValue()
	{
		return $this->last_value;
	}
	public function updateGroup($model, $key, $index)
	{
		// have we've got the value on willUpdateGroup?
		if( $this->got_value == false ) {
			if( $this->value instanceOf \Closure ) {
				$this->current_value = call_user_func($this->value, $model, $key, $index, $this->grid);
			} else {
				$this->current_value = ArrayHelper::getValue($model, $this->column);
			}
		} else {
			$this->got_value = false;
		}
		if( $this->last_value !== $this->current_value) {
			if( $this->last_value != null ) {
				$this->group_change = self::NEW_FOOTER_AND_HEADER;
			} else {
				$this->group_change = self::NEW_HEADER;
			}
			$this->last_value = $this->current_value;
		} else {
			$this->group_change = self::NO_GROUP_CHANGE;
		}
		return $this->group_change;
	}

	public function willUpdateGroup($model, $key, $index)
	{
		if( $this->value instanceOf \Closure ) {
			$this->current_value = call_user_func($this->value, $model, $key, $index, $this->grid);
		} else {
			$this->current_value = ArrayHelper::getValue($model, $this->column);
		}
		$this->got_value = true;
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
					'class' => 'grid-group-foot-total-' . strval($this->level) . ' grid-group-foot-' . strval($this->level)
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
			Html::tag('div', Yii::t('churros',"Totals ") . $content, [
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
						$this->summaryValues[$this->level][$kc], $column->format),
						GridView::fetchColumnOptions($column, $this->level)));
			} else {
				$ret .= Html::tag('td', '');
			}
		}
		return $ret;
	}

	public function resetSummaries($summary_columns, $report_level, $max_levels)
	{
		for( $l = $report_level; $l <= $max_levels; ++$l ) {
			if( !isset($this->summaryValues[$l]) ) {
				$this->summaryValues[$l] = [];
			}
			foreach( $summary_columns as $kc => $summary) {
				switch( $summary ) {
				case 'f_sum':
				case 'f_count':
					$this->summaryValues[$l][$kc] = 0;
					break;
				case 'f_avg':
					$this->summaryValues[$l][$kc][0] = 0;
					$this->summaryValues[$l][$kc][1] = 0;
					break;
				case 'f_max':
					$this->summaryValues[$l][$kc] = null;
					break;
				case 'f_min':
					$this->summaryValues[$l][$kc] = null;
					break;
				case 'f_concat':
				case 'f_distinct_concat':
					$this->summaryValues[$l][$kc] = [];
					break;
				}
			}
		}
	}

	public function updateSummaries($summary_columns, $report_level, $row_values)
	{
		// same in GridView::updateSummaries
		for( $l = 1; $l <= $report_level; ++$l ) {
			foreach( $summary_columns as $key => $summary) {
				$kc = str_replace('.', '_', $key);
				switch( $summary ) {
				case 'f_sum':
					$this->summaryValues[$l][$key] += $row_values[$kc];
					break;
				case 'f_count':
					$this->summaryValues[$l][$key] ++;
					break;
				case 'f_avg':
					$this->summaryValues[$l][$key][0] += $row_values[$kc];
					$this->summaryValues[$l][$key][1] ++;
					break;
				case 'f_max':
					if( $this->summaryValues[$l][$key] == null ) {
						$this->summaryValues[$l][$key] = $row_values[$kc];
					} else if( $this->summaryValues[$l][$key] < $row_values[$kc] ) {
						$this->summaryValues[$l][$key] = $row_values[$kc];
					}
					break;
				case 'f_min':
					if( $this->summaryValues[$l][$key] == null ) {
						$this->summaryValues[$l][$key] = $row_values[$kc];
					} else if( $this->summaryValues[$l][$key] > $row_values[$kc] ) {
						$this->summaryValues[$l][$key] = $row_values[$kc];
					}
					break;
				case 'f_concat':
					$this->summaryValues[$l][$key][] = $row_values[$kc];
					break;
				case 'f_distinct_concat':
					if (!in_array($row_values[$key], $this->summaryValues[$l][$kc])) {
						$this->summaryValues[$l][$key][] = $row_values[$kc];
					}
					break;
				}
			}
		}
	}
}
