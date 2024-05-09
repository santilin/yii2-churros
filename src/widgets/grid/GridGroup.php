<?php
/**
 * A group for a grid
 */

namespace santilin\churros\widgets\grid;

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
	public $header = true;
	public $labels = [];
	public $header_format;
	public $header_label;
	public $footer = true;
	public $footer_format;
	public $footer_label;
	public $value;
	public $format;
	public $orderby;
	public $show_column = true;

	public $level = 0;
	protected $group_change = false;
	protected $got_value = false, $last_value = null, $current_value = null;
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
		} else if (ArrayHelper::KeyExists($this->value, $model)) {
				$this->current_value = $model->{$this->value};
			} else {
				$this->current_value = $this->grid->columns[$this->value]->getDataCellValue($model, $key, $index);
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
		} else if (ArrayHelper::KeyExists($this->value, $model)) {
			$this->current_value = $model->{$this->value};
		} else {
			$this->current_value = $this->grid->columns[$this->value]->getDataCellValue($model, $key, $index);
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
		if ($this->grid->onlySummary && $this->level < count($this->grid->groups)) {
			return '';
		}
		$content = $this->header;
		if ($content instanceOf \Closure ) {
			$content = call_user_func($content, $model, $key, $index, $this);
		}
		if ($content === true || $content === null) {
			$content = $this->header_label . ' ';
			$format = $this->header_format?:$this->format?:'raw';
			switch($format) {
				case 'raw':
					$content .= $this->current_value;
					break;
				default:
					$content .= Yii::$app->formatter->format($this->current_value, $format);
					break;
			}
		}
		if( $content !== false) {
			$content = strtr($content, [
				'{group_value}' => $this->current_value,
				'{group_header_label}' => $this->header_label,
				'{group_footer_label}' => $this->footer_label,
			]);
			Html::addCssClass($tdoptions, "report group-head-{$this->column} group-head-{$this->level}");
			return Html::tag('td', $content, $tdoptions);
		} else {
			return '';
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
			} else {
				$value = $this->summaryValues[$this->level][$kc];
				if( $this->level == count($this->grid->groups) ) {
					Html::addCssClass($tdoptions, "report detail");
				} else {
					Html::addCssClass($tdoptions, "report group-foot-$column group-foot-{$this->level}");
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
		$content = $this->footer;
		if ($content instanceOf \Closure ) {
			$content = call_user_func($content, $model, $key, $index, $this);
		}
		if ($content === true || $content === null) {
			return $this->getSummaryContent($summary_columns, $content);
		}
		$ret = '';
		if( $content !== false) {
			$content = strtr($content, [
				'{group_value}' => $this->current_value,
				'{group_header_label}' => $this->header_label,
				'{group_footer_label}' => $this->footer_label,
			]);
			Html::addCssClass($tdoptions, "report group-foot-total-$column group-foot-total-{$this->level}");
			$ret = Html::tag('td', $content, $tdoptions);
		}
		return $ret;
	}

	public function getSummaryContent($summary_columns, $content)
	{
		$colspan = 0;
		foreach ($this->grid->columns as $column) {
			if( $column->visible ) {
				if (!$column instanceof $this->grid->dataColumnClass) {
					continue;
				}
				if( !isset($summary_columns[$column->attribute]) ) {
					$colspan++;
				} else {
					break;
				}
			}
		}
		$tdoptions = [
			'class' => 'report group-total-label group-foot-' . strval($this->level),
			'colspan' => $colspan,
		];
		$ret = Html::tag('td', Yii::t('churros', "Totals") . ' ' . $content, $tdoptions );
		$nc = 0;
		foreach( $this->grid->columns as $column ) {
			if (!$column instanceof $this->grid->dataColumnClass) {
				continue;
			}
			$kc = $column->attribute;
			if( $nc++ < $colspan ) {
				continue;
			}
			$classes = [
				'w1'
			];
			if( ($column->format?:'raw') != 'raw' ) {
				$classes[] = "report {$column->format}";
			}
			if( isset($summary_columns[$kc]) ) {
				$classes[] = 'report group-foot-' . strval($this->level);
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
				case 'sum':
				case 'count':
				case 'distinct_sum':
				case 'distinct_count':
					$this->summaryValues[$l][$kc] = 0;
					break;
				case 'avg':
				case 'distinct_avg':
					$this->summaryValues[$l][$kc][0] = 0;
					$this->summaryValues[$l][$kc][1] = 0;
					break;
				case 'max':
					$this->summaryValues[$l][$kc] = null;
					break;
				case 'min':
					$this->summaryValues[$l][$kc] = null;
					break;
				case 'concat':
				case 'distinct_concat':
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
				case 'sum':
					$this->summaryValues[$l][$key] += $row_values[$kc];
					break;
				case 'distinct_sum':
					if (!in_array($row_values[$key], $this->summaryValues[$l][$kc])) {
						$this->summaryValues[$l][$key] += $row_values[$kc];
					}
					break;
				case 'count':
					$this->summaryValues[$l][$key] ++;
					break;
				case 'distinct_count':
					if (!in_array($row_values[$key], $this->summaryValues[$l][$kc])) {
						$this->summaryValues[$l][$key] ++;
					}
					break;
				case 'avg':
					$this->summaryValues[$l][$key][0] += $row_values[$kc];
					$this->summaryValues[$l][$key][1] ++;
					break;
				case 'distinct_count':
					if (!in_array($row_values[$key], $this->summaryValues[$l][$kc])) {
						$this->summaryValues[$l][$key][0] += $row_values[$kc];
						$this->summaryValues[$l][$key][1] ++;
					}
					break;
				case 'max':
					if( $this->summaryValues[$l][$key] == null ) {
						$this->summaryValues[$l][$key] = $row_values[$kc];
					} else if( $this->summaryValues[$l][$key] < $row_values[$kc] ) {
						$this->summaryValues[$l][$key] = $row_values[$kc];
					}
					break;
				case 'min':
					if( $this->summaryValues[$l][$key] == null ) {
						$this->summaryValues[$l][$key] = $row_values[$kc];
					} else if( $this->summaryValues[$l][$key] > $row_values[$kc] ) {
						$this->summaryValues[$l][$key] = $row_values[$kc];
					}
					break;
				case 'concat':
					$this->summaryValues[$l][$key][] = $row_values[$kc];
					break;
				case 'distinct_concat':
					if (!in_array($row_values[$key], $this->summaryValues[$l][$kc])) {
						$this->summaryValues[$l][$key][] = $row_values[$kc];
					}
					break;
				}
			}
		}
	}
}
