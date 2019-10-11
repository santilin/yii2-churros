<?php
/**
 * Just Another Grid Widget
 */
namespace santilin\churros\grid;

use Yii;
use yii\helpers\Html;
use kartik\grid\GridView as BaseGridView;
use santilin\churros\grid\GridGroup;

class GridView extends BaseGridView
{
	/**
	 * The group headers and footers definitions
	 */
	public $groups = [];
	/**
	 * The column to group
	 */
	public $column = null;
	public $totalsRow = false;

	protected $summaryColumns = [];
	protected $summaryValues = [];
	protected $recno;

	public function init()
	{
		$this->initGroups(); // must be done before initColumns
		parent::init();
		if( count($this->groups) != 0 || $this->totalsRow) {
			$this->beforeRow = function($model, $key, $index, $grid) {
				return $grid->groupHeader($model, $key, $index, $grid);
			};
			$this->afterRow = function($model, $key, $index, $grid) {
				return $grid->finalRow($model, $key, $index, $grid);
			};
		}
		$this->recno = 0;
		$this->initSummaryColumns();
		$this->initSorting();
	}

	/**
	 * Creates the constant array of summary columns to be passed to the
	 * summary functions of each group
	 */
	protected function initSummaryColumns()
	{
		foreach( $this->columns as $kc => $column ) {
			if( isset($column->pageSummary) && $column->pageSummary != 0 ) {
				$this->summaryColumns[$kc] = $column->pageSummaryFunc;
				switch( $column->pageSummaryFunc) {
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
				$this->summaryValues[$kc] = null;
			}
		}
		$this->resetSummaries(count($this->groups));
	}

	protected function initGroups()
	{
		$level = 1;
		foreach( $this->groups as $kg => $group_def ) {
            if (is_string($group_def)) {
                $group = $this->createGroup($group_def);
            } else {
                $group = Yii::createObject(array_merge([
                    'class' => GridGroup::className(),
                    'grid' => $this,
                ], $group_def));
            }
            if (!$group->visible) {
                unset($this->groups[$kg]);
                continue;
            }
            $group->level = $level++;
            $this->groups[$kg] = $group;
            // Hide the group column
            if( $group->column ) {
  				$this->columns[$group->column]['visible'] = false;
			}
        }
	}

	// Not all group columns are defined in the grid
// 	protected function initGroupLabels()
// 	{
// 		foreach( $this->groups as $key => $group ) {
// 			if( isset($group->label) ) {
// 				continue;
// 			}
// 			if( !isset($group->header_label) ) {
// 				$c = $this->columns;
// 				$group->header_label = $this->columns[$group->column]['label'];
// 			}
// 			if( !isset($group->footer_label) ) {
// 				$group->footer_label = $this->columns[$group->column]['label'];
// 			}
// 		}
// 	}

	/**
	 * Appends the groups orders to the default or current orders
	 */
	protected function initSorting()
	{
		$s = $this->dataProvider->getSort();
		$s->enableMultiSort = true;
		$def_order = $this->dataProvider->getSort()->getAttributeOrders(false);
		$new_def_order = [];
		$nc = 0;
		$def_order_columns = array_keys($def_order);
		foreach( $this->groups as $key => $group ) {
			if( isset($def_order_columns[$nc]) && $def_order_columns[$nc] == $group->column) {
				$new_def_order[$group->column] = $def_order[$group->column];
				$def_order[$group->column] = null;
			} else {
				$new_def_order = [ $group->column => SORT_ASC ];
			}
			++$nc;
		}
		foreach( $def_order as $key => $value ) {
			if( $value === null ) {
				unset($def_order[$key]);
			}
		}
		$new_def_order += $def_order;
		$s->setAttributeOrders($new_def_order);
	}

	protected function groupHeader($model, $key, $index, $grid)
	{
		$ret = '';
		$tdoptions = [ 'colspan' => count($this->columns) ];
		$summarized = false;
		$this->recno++;
		$this->updateSummaries($model);
		foreach( $this->groups as $kg => $group ) {
			// close previous footer on group change
			if( $group->footer &&
				$group->willUpdateGroup($model, $key, $index ) ) {
				$ret .= Html::tag('tr',
					$group->getFooterContent($this->summaryColumns, $model, $key, $index, $tdoptions));
				$this->resetSummaries($group->level);
			}
			$this->updateGroupSummaries($group->level, $model);
			if( $group->updateGroup($model, $key, $index) && $group->header ) {
				$ret .= Html::tag('tr',
					$group->getHeaderContent($model, $key, $index,  $tdoptions));
			}
		}
		return $ret;
	}

	public function finalRow($model, $key, $index, $grid)
	{
		// Once the dataprovider has been consumed, print all the group footers and the grand total
		if( $this->recno < $this->dataProvider->getCount() ) {
			return '';
		}
		$tdoptions = [ 'colspan' => count($this->columns) ];
		$ret = '';
		foreach( $this->groups as $kg => $group ) {
			if( $group->footer ) {
				$ret .= Html::tag('tr',
					$group->getFooterContent($this->summaryColumns, $model, $key, $index, $tdoptions));
			}
		}
		if( $this->totalsRow ) {
			$ret .= Html::tag('tr',
				$this->getFooterSummary($this->summaryColumns, $tdoptions));
		}
		return $ret;
	}

	public function getFooterSummary($summary_columns, $tdoptions)
	{
		$colspan = 0;
		foreach( $this->columns as $kc => $column ) {
			if( !isset($summary_columns[$kc]) ) {
				$colspan++;
			} else {
				break;
			}
		}
		$ret = Html::tag('td',
			Html::tag('div', "Totales ", [
				'class' => 'grid-group-total' ]),
			['colspan' => $colspan]);
		$nc = 0;
		foreach( $this->columns as $kc => $column ) {
			if( $nc++ < $colspan ) {
				continue;
			}
			if( isset($summary_columns[$kc]) ) {
				$ret .= Html::tag('td', Html::tag('div',
					$this->formatter->format(
						$this->summaryValues[$kc], $column->format),
						self::fetchColumnOptions($column, 0)));
			} else {
				$ret .= Html::tag('td', '');
			}
		}
		return $ret;
	}

	// copied from kartik data column
	static public function fetchColumnOptions($column, $level = null)
    {
		if( $level == null ) {
			$options = [ 'class' => "grid-group-total" ];
		} else {
			$options = [ 'class' => "grid-group-foot-$level" ];
		}
        if ($column->hidden === true) {
            Html::addCssClass($options, 'kv-grid-hide');
        }
        if ($column->hiddenFromExport === true) {
            Html::addCssClass($options, 'skip-export');
        }
        if (is_array($column->hiddenFromExport) && !empty($column->hiddenFromExport)) {
            $tag = 'skip-export-';
            $css = $tag . implode(" {$tag}", $column->hiddenFromExport);
            Html::addCssClass($options, $css);
        }
        if( $column->hAlign != '' ) {
            Html::addCssClass($options, "kv-align-{$column->hAlign}");
        }
        if ($column->noWrap) {
            Html::addCssClass($options, GridView::NOWRAP);
        }
        if( $column->vAlign != '' ) {
            Html::addCssClass($options, "kv-align-{$column->vAlign}");
        }
        if (trim($column->width) != '') {
            Html::addCssStyle($options, "width:{$column->width};");
        }
        return $options;
    }



    /**
     * @inheritdoc
     * Redefined to show the column of the error in case of error
     */
    public function renderFilters()
    {
        if ($this->filterModel !== null) {
            $cells = [];
            foreach ($this->columns as $column) {
                /* @var $column Column */
                try {
					$cells[] = $column->renderFilterCell();
				} catch( \Exception $e ) {
					throw new \Exception($column->attribute . ": " . $e->getMessage());
				}
            }
            return Html::tag('tr', implode('', $cells), $this->filterRowOptions);
        }
        return '';
    }

	public function resetSummaries($level)
	{
		foreach( $this->groups as $kg => $group ) {
			if( $group->level >= $level ) {
				$group->resetSummaries($this->summaryColumns);
			}
		}
	}

	public function updateGroupSummaries($level, $model)
	{
		foreach( $this->groups as $kg => $group ) {
			if( $group->level <= $level ) {
				$group->updateSummaries($this->summaryColumns, $model);
			}
		}
	}

	public function updateSummaries($model)
	{
		// same in GridGroup::updateSummaries
		foreach( $this->summaryColumns as $key => $summary) {
			$kc = str_replace('.', '_', $key);
			switch( $summary ) {
			case 'f_sum':
				$this->summaryValues[$key] += $model[$kc];
				break;
			case 'f_count':
				$this->summaryValues[$key] ++;
				break;
			case 'f_avg':
				$this->summaryValues[$key][0] += $model[$kc];
				$this->summaryValues[$key][1] ++;
				break;
			case 'f_max:':
				if( $this->summaryValues[$key] < $model[$kc] ) {
					$this->summaryValues[$key] = $model[$kc];
				}
				break;
			case 'f_min:':
				if( $this->summaryValues[$key] > $model[$kc] ) {
					$this->summaryValues[$key] = $model[$kc];
				}
				break;
			case 'f_concat:':
				$this->summaryValues[$key][] = $model[$kc];
				break;
			case 'f_distinct_concat:':
				if (!in_array($model[$key], $this->summaryValues[$kc])) {
					$this->summaryValues[$key][] = $model[$kc];
				}
				break;
			}
		}
	}

}
