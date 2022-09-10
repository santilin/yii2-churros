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
	protected $last_group_changed = false;

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
		if( $this->grid->onlySummary && $this->level < count($this->grid->groups) ) {
			return '';
		}
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
			Html::addCssClass($tdoptions, 'reportview-group-head-' . strval($this->level) . ' w1');
			return Html::tag('td', $content, $tdoptions);
		}
	}

	public function getFooterContent($summary_columns, $model, $key, $index, $tdoptions)
	{
		if( $this->grid->onlySummary && $this->level > count($this->grid->groups) ) {
			return $this->getOnlyTotalsContent($summary_columns, $model, $key, $index, $tdoptions);
		} else {
			return $this->getStandardFooterContent($summary_columns, $model, $key, $index, $tdoptions);
		}
	}

	protected function getOnlyTotalsContent($summary_columns, $model, $key, $index, $tdoptions)
	{
		$ret = '';
		foreach( $this->grid->columns as $kc => $column ) {
			if( !isset($summary_columns[$kc]) ) {
				$value = $model[$kc];
				$tdoptions = [];
			} else {
				$value = $this->summaryValues[$this->level][$kc];
				if( $this->level == count($this->grid->groups) ) {
					$tdoptions = [
						'class' => 'reportview-detail w1',
					];
				} else {
					$tdoptions = [
						'class' => 'reportview-group-foot-' . strval($this->level-1) . ' w1',
					];
				}
			}
			$ret .= Html::tag('td',
				$this->grid->formatter->format($value, $column->format),
					GridView::fetchColumnOptions($column, $tdoptions));
		}
		return $ret;
	}


	protected function getStandardFooterContent($summary_columns, $model, $key, $index, $tdoptions)
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
			Html::addCssClass($tdoptions, 'reportview-group-foot-total-' . strval($this->level) . ' reportview-group-foot-' . strval($this->level) . ' w1');
			$ret = Html::tag('td', $content, $tdoptions);
		}
		return $ret;
	}

	public function getSummaryContent($summary_columns, $content)
	{
		$colspan = 0;
		foreach( $this->grid->columns as $column ) {
			if( $column->visible ) {
				if( !isset($summary_columns[$column->attribute]) ) {
					$colspan++;
				} else {
					break;
				}
			}
		}
		$tdoptions = [
			'class' => 'reportview-group-total-label reportview-group-foot-' . strval($this->level) . ' w1',
			'colspan' => $colspan,
		];
		$ret = Html::tag('td', Yii::t('churros', "Totals") . ' ' . $content, $tdoptions );
		$nc = 0;
		foreach( $this->grid->columns as $column ) {
			$kc = $column->attribute;
			if( $nc++ < $colspan ) {
				continue;
			}
			$classes = [
				'w1'
			];
			if( ($column->format?:'raw') != 'raw' ) {
				$classes[] = "reportview-{$column->format}";
			}
			if( isset($summary_columns[$kc]) ) {
				$classes[] = 'reportview-group-foot-' . strval($this->level);
				$ret .= Html::tag('td',
					$this->grid->formatter->format(
						$this->summaryValues[$this->level][$kc], $column->format),
						[ 'class' => join(' ', $classes) ]);
			} else {
				$ret .= Html::tag('td', '', [ 'class' => join(' ', $classes) ]);
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
