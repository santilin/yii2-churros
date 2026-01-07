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
	public string $column;
	public bool|array|null $header = true;
	public bool|array|null $footer = true;
	public $value;
	public $show_column = true;
	public array $orderBy = [];

	protected int $level = 0;
	protected $group_change = false;
	protected $got_value = false, $last_value = null, $current_value = null;
	protected $summaryValues = [];
	protected $last_group_changed = false;

	public function getLevel(): int
	{
		return $this->level;
	}

	public function setLevel(int $level): void
	{
		$this->level = $level;
	}

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
		if ($this->got_value === false) {
			if ($this->value instanceOf \Closure) {
				$this->current_value = call_user_func($this->value, $model, $key, $index, $this->grid);
			} else if (ArrayHelper::KeyExists($this->value, $model)) {
				$this->current_value = $model->{$this->value};
			} else {
				$this->current_value = $this->grid->columns[$this->value]->getDataCellValue($model, $key, $index);
			}
		} else {
			$this->got_value = false;
		}
		if ($this->last_value !== $this->current_value) {
			if ($this->last_value != null) {
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
		if ($this->value instanceOf \Closure) {
			$this->current_value = call_user_func($this->value, $model, $key, $index, $this->grid);
		} else {
			$this->current_value = ArrayHelper::getValue($model, $this->value);
		// } else {
		// 	$this->current_value = $this->grid->columns[$this->value]->getDataCellValue($model, $key, $index);
		}
		$this->got_value = true;
		if ($this->last_value !== $this->current_value) {
			if ($this->last_value != null) {
				return true;
			}
		}
		return false;
	}

	public function getHeaderContent($model, $key, $index, array $tdoptions): string
	{
		if ($this->grid->onlySummary && $this->level < count($this->grid->groups)) {
			return '';
		}
		$content = $this->header ?? null;
		if ($content !== false ) {
			$content = ($this->header['label'] ?? null);
			if ($content instanceof \Closure) {
				$content = call_user_func($content, $model, $this);
			}
			if ($content === null) {
				$content = "{group_value}";
			}
		}
		if ($content !== false) {
			$content = strtr($content, [
				'{group_value}' => $this->current_value,
				'{group_header_label}' => $this->header['label'] ?? '',
				'{group_footer_label}' => $this->footer['label'] ?? '',
			]);
			$inverse_level = count($this->grid->groups) - $this->level + 1;
			if ($this->header && isset($this->header['rowOptions'])) {
				if ($this->header['rowOptions'] instanceof \Closure) {
					$options = call_user_func($this->header['rowOptions'], $this->combineSummaryValues($this->level), $this);
				} else {
					$options = $this->header['rowOptions'];
				}
				$tdoptions = array_merge($tdoptions, $options);
			}
			Html::addCssClass($tdoptions, "group-head group-head-$inverse_level {$this->column}");
			return Html::tag('td', $content, $tdoptions);
		} else {
			return '';
		}
	}

	public function getFooterRow(array $summary_columns, $model, $key, array $row_options = []): string
	{
        if ($this->footer && isset($this->footer['rowOptions'])) {
			if ($this->footer['rowOptions'] instanceof \Closure) {
				$options = call_user_func($this->footer['rowOptions'], $this->combineSummaryValues($this->level), $this);
			} else {
				$options = $this->footer['rowOptions'];
			}
			$row_options = array_merge($row_options, $options);
        }
		Html::addCssClass($row_options, 'group-foot-' . strval($this->level));
		if ($this->grid->onlySummary && $this->level > count($this->grid->groups)) {
			return Html::tag('tr', $this->getOnlyTotalsContent($tsummary_columns, $model, $key), $row_options);
		} else {
			return Html::tag('tr', $this->getStandardFooterContent($summary_columns, $model, $key), $row_options);
		}
	}

	protected function getOnlyTotalsContent($summary_columns, $model, $key): string
	{
		$ret = '';
		$first_td_options = [];
		foreach ($this->grid->columns as $kc => $column) {
			if (!isset($summary_columns[$kc])) {
				$value = $model[$kc];
			} else {
				$value = $this->summaryValues[$this->level][$kc];
				if ($this->level == count($this->grid->groups)) {
					Html::addCssClass($first_td_options, "detail");
				} else {
					Html::addCssClass($first_td_options, "group-foot-$column group-foot-{$this->level}");
				}
			}
			$ret .= Html::tag('td',
				$this->grid->formatter->format($value, $column->format), $first_td_options);
		}
		return $ret;
	}


	protected function getStandardFooterContent($summary_columns, $model, $key)
	{
		$colspan = 0;
		foreach ($this->grid->columns as $kc => $column) {
			if ($column->visible) {
				if (!$column instanceof $this->grid->dataColumnClass) {
					continue;
				}
				if (!isset($summary_columns[$kc])) {
					$colspan++;
				} else {
					break;
				}
			}
		}
		$label_options = [
			'class' => 'group-total-label',
			'colspan' => $colspan,
		];
		$label = $this->footer['label'] ?? null;
		if ($label instanceOf \Closure) {
			$label = call_user_func($label, $this->combineSummaryValues($this->level), $model, $key, $this);
		}
		if ($label === true || $label === null) {
			$group_column = $this->grid->findColumn($this->column);
			if ($group_column) {
				$label = $group_column->label ?: $this->column;
				if ($label != '') {
					$label = ' '  . mb_strtolower($label) . ' ';
				}
			}
		} else {
			$label = strtr($label, [
				'{group_value}' => $this->last_value,
				'{group_header_label}' => $this->header['label'] ?? '',
				'{group_footer_label}' => $this->footer['label'] ?? '',
			]);
		}
		return $this->getSummaryContent($summary_columns, $label, $colspan, $label_options);
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
	public function getSummaryContent($summary_columns, string $label, int $colspan, array $label_options)
	{
		$ret = Html::tag('td', Yii::t('churros', 'Totals {label}', [
			'label' => $label ?: '',
		]), $label_options);
		$nc = 0;
		foreach ($this->grid->columns as $kc => $column) {
			if (!$column instanceof $this->grid->dataColumnClass) {
				$ret .= '<td></td>';
				continue;
			}
			if ($nc++ < $colspan) {
				continue;
			}
			$classes = [
				'w1'
			];
			$format = $this->grid->formatOfColumn($column);
			if ($format !== '') {
				$classes[] = "format-$format";
			}
			if (isset($summary_columns[$kc])) {
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
		for( $l = $report_level; $l <= $max_levels; ++$l) {
			if (!isset($this->summaryValues[$l])) {
				$this->summaryValues[$l] = [];
			}
			foreach ( $summary_columns as $kc => $summary) {
				switch( $summary) {
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
		for( $l = 1; $l <= $report_level; ++$l) {
			foreach ($summary_columns as $key => $summary) {
				// $kc = str_replace('.', '_', $key);
				switch( $summary) {
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
					if ($this->summaryValues[$l][$key] == null) {
						$this->summaryValues[$l][$key] = $row_values[$key];
					} else if ($this->summaryValues[$l][$key] < $row_values[$key]) {
						$this->summaryValues[$l][$key] = $row_values[$key];
					}
					break;
				case 'min':
					if ($this->summaryValues[$l][$key] == null) {
						$this->summaryValues[$l][$key] = $row_values[$key];
					} else if ($this->summaryValues[$l][$key] > $row_values[$key]) {
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

	public function combineSummaryValues(int $level): array
	{
		$ret = [];
		$grid_columns = $this->grid->columns;
		foreach ($this->summaryValues[$level] as $kc => $value) {
			$ret[$grid_columns[$kc]->attribute] = $value;
		}
		return $ret;
	}

}
