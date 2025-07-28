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
			$this->current_value = ArrayHelper::getValue($model, $this->value);
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
			Html::addCssClass($tdoptions, "group-head group-head-{$this->level} {$this->column}");
			return Html::tag('td', $content, $tdoptions);
		} else {
			return '';
		}
	}

	public function getFooterContent($summary_columns, $model, $key, $index, $tdoptions)
	{
		if ($this->grid->onlySummary && $this->level > count($this->grid->groups)) {
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
			$group_column = $this->grid->findColumn($this->column);
			$label = '';
			if ($group_column) {
				$label = $group_column->label ?: $this->column;
				if ($label != '') {
					$label = ' '  . mb_strtolower($label) . ' ';
				}
			}
			return $this->getSummaryContent($summary_columns, $label . $this->current_value);
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

	/**
	 * Generates the summary content for a group in a grid view.
	 *
	 * This function creates a summary row for a group in a grid, including a label cell
	 * and cells for summary values of specified columns.
	 *
	 * @param array $summary_columns An associative array of columns to be summarized,
	 *                               where keys are column attributes.
	 * @param string $content Additional content to be appended to the "Totals" label.
	 *
	 * @return string HTML string representing the summary row.
	 *
	 * The resulting row includes:
	 * - A label cell with "Totals" and additional content
	 * - Cells for each summarized column, displaying formatted summary values
	 * - Empty cells for non-summarized columns
	 *
	 * CSS classes are applied to cells based on:
	 * - Column format
	 * - Group footer level
	 * - Whether the column is summarized
	 *
	 */
	public function getSummaryContent($summary_columns, string $current_group_value)
	{
		$colspan = 0;
		foreach ($this->grid->columns as $kc => $column) {
			if( $column->visible ) {
				if (!$column instanceof $this->grid->dataColumnClass) {
					continue;
				}
				if( !isset($summary_columns[$kc]) ) {
					$colspan++;
				} else {
					break;
				}
			}
		}
		$tdoptions = [
			'class' => 'group-total-label group-foot-' . strval($this->level),
			'colspan' => $colspan,
		];
		$ret = Html::tag('td', Yii::t('churros', 'Totals') . ' ' . $current_group_value, $tdoptions);
		$nc = 0;
		foreach ($this->grid->columns as $kc => $column) {
			if (!$column instanceof $this->grid->dataColumnClass) {
				continue;
			}
			if( $nc++ < $colspan ) {
				continue;
			}
			$classes = [
				'w1'
			];
			if (is_array($column->format)) {
				$classes[] = "format-{$column->format[0]}";
			} else if ($column->format?:'raw' != 'raw') {
				$classes[] = "format-{$column->format}";
			}
			if( isset($summary_columns[$kc]) ) {
				$classes[] = 'group-foot-' . strval($this->level);
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
			foreach ($summary_columns as $key => $summary) {
				// $kc = str_replace('.', '_', $key);
				switch( $summary ) {
				case 'sum':
					$this->summaryValues[$l][$key] += $row_values[$key];
					break;
				case 'distinct_sum':
					if (!in_array($row_values[$key], $this->summaryValues[$l][$key])) {
						$this->summaryValues[$l][$key] += $row_values[$key];
					}
					break;
				case 'count':
					$this->summaryValues[$l][$key] ++;
					break;
				case 'distinct_count':
					if (!in_array($row_values[$key], $this->summaryValues[$l][$key])) {
						$this->summaryValues[$l][$key] ++;
					}
					break;
				case 'avg':
					$this->summaryValues[$l][$key][0] += $row_values[$key];
					$this->summaryValues[$l][$key][1] ++;
					break;
				case 'distinct_count':
					if (!in_array($row_values[$key], $this->summaryValues[$l][$key])) {
						$this->summaryValues[$l][$key][0] += $row_values[$key];
						$this->summaryValues[$l][$key][1] ++;
					}
					break;
				case 'max':
					if( $this->summaryValues[$l][$key] == null ) {
						$this->summaryValues[$l][$key] = $row_values[$key];
					} else if( $this->summaryValues[$l][$key] < $row_values[$key] ) {
						$this->summaryValues[$l][$key] = $row_values[$key];
					}
					break;
				case 'min':
					if( $this->summaryValues[$l][$key] == null ) {
						$this->summaryValues[$l][$key] = $row_values[$key];
					} else if( $this->summaryValues[$l][$key] > $row_values[$key] ) {
						$this->summaryValues[$l][$key] = $row_values[$key];
					}
					break;
				case 'concat':
					$this->summaryValues[$l][$key][] = $row_values[$key];
					break;
				case 'distinct_concat':
					if (!in_array($row_values[$key], $this->summaryValues[$l][$key])) {
						$this->summaryValues[$l][$key][] = $row_values[$key];
					}
					break;
				}
			}
		}
	}
}
