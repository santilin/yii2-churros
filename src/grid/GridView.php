<?php
/**
 * Just Another Grid Widget
 * https://dev.mysql.com/doc/refman/8.0/en/group-by-functions.html
 */
namespace santilin\churros\grid;

use Yii;
use yii\helpers\Html;
use kartik\grid\GridView as BaseGridView;
use santilin\churros\grid\GridGroup;

class GridView extends BaseGridView
{
	/**
	 * The groups headers and footers definitions
	 */
	public $groups = [];
	/**
	 * The column to group
	 */
	public $column = null;
	public $totalsRow = false;
	public $onlyTotals = false;

	protected $summaryColumns = [];
	protected $summaryValues = [];
	protected $previousModel = null;
	protected $recno;
	protected $current_level = 0;


	public function __construct($config = [])
	{
		if (empty($config['pager']) ) {
			$config['pager'] = [
				'firstPageLabel' => '<<',
				'lastPageLabel' => '>>',
				'nextPageLabel' => '>',
				'prevPageLabel' => '<',
			];
		}
		parent::__construct($config);
	}

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
		$this->resetGroupsSummaries();
		$this->initSorting();
	}

	/**
	 * Creates the constant array of summary columns to be used by the
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
						$this->summaryValues[$kc][0] = 0;
						$this->summaryValues[$kc][1] = 0;
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
	}

	protected function resetGroupsSummaries()
	{
		foreach( $this->groups as $kg => $group )  {
			$group->resetSummaries($this->summaryColumns, 0, count($this->groups));
		}
	}

	protected function initGroups()
	{
		$this->current_level = 0; // details
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
            if( $group->column /*&& !$this->onlyTotals*/ ) {
  				$this->columns[str_replace('.', '_', $kg)]['visible'] = false;
			}
        }
	}

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

	// override
	public function renderTableRow($model, $key, $index)
    {
		$ret = parent::renderTableRow($model, $key, $index);
		if( !$this->onlyTotals ) {
			return $ret;
		} else {
			return null;
		}
    }

	// override
	public function getPageSummaryRow()
	{
		$p = $this->dataProvider->getPagination();
		if( $p && $p->getPageCount() > 1 ) {
			return '<td colspan="42">No muestro totales porque no se están mostrando todos los registros.</tr>';
		} else {
			return parent::getPageSummaryRow();
		}
	}


	protected function groupHeader($model, $key, $index, $grid)
	{
		if( $this->previousModel === null ) {
			$this->previousModel = $model;
		}
		$ret = '';
		$tdoptions = [ 'colspan' => count($this->columns) ];
		$this->recno++;
		// close previous footers on group change
		$updated_groups = [];
		$previous_updated = false;
		foreach( $this->groups as $kg => $group ) {
			$this_updated = $group->willUpdateGroup($model, $key, $index);
			if( $previous_updated ) {
				$updated_groups[$kg] = true;
			} else {
				$previous_updated = $updated_groups[$kg] = $this_updated;
			}
		}
		foreach( array_reverse($this->groups) as $kg => $group ) {
			if( $updated_groups[$kg] ) {
				if ($group->footer ) {
					$ret .= Html::tag('tr',
						$group->getFooterContent($this->summaryColumns,
						$this->previousModel, $key, $index, $tdoptions));
				}
				$group->resetSummaries($this->summaryColumns, $this->current_level, count($this->groups));
				$this->current_level--;
			} else {
				break;
			}
		}
		$this->updateReportSummaries($model);
		$updated_groups = [];
		foreach( $this->groups as $kg => $group ) {
			$updated_groups[$kg] = $group->updateGroup($model, $key, $index);
		}
		$first_header_shown = false;
		foreach( $this->groups as $kg => $group ) {
			if( $updated_groups[$kg] || $first_header_shown ) {
				$first_header_shown = true;
				if( $group->header ) {
					if( ($this->onlyTotals && $group->level < count($this->groups))
						|| $group->level <= count($this->groups) ) {
						$ret .= Html::tag('tr',
							$group->getHeaderContent($model, $key, $index, $tdoptions));
					}
				}
				$this->current_level++;
				$group->resetSummaries($this->summaryColumns, $this->current_level, count($this->groups));
			}
			$group->updateSummaries($this->summaryColumns, $this->current_level, $model);
		}
		$this->previousModel = $model;
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
		foreach( array_reverse($this->groups) as $kg => $group ) {
			if( $group->footer ) {
				$ret .= Html::tag('tr',
					$group->getFooterContent($this->summaryColumns, $model, $key, $index, $tdoptions));
			}
		}
		if( $this->totalsRow ) {
			$ret .= Html::tag('tr',
				$this->getFooterSummary($this->summaryColumns, $tdoptions),
				[ 'class' => 'report-grand-total']);
		}
		return $ret;
	}

	public function getFooterSummary($summary_columns, $tdoptions)
	{
		if( count($summary_columns) == 0 ) {
			return '';
		}
		$colspan = 0;
		foreach( $this->columns as $kc => $column ) {
			if( !isset($summary_columns[$kc]) ) {
				$colspan++;
			} else {
				break;
			}
		}
		$ret = '';
		if( $colspan!=0) {
			$ret = Html::tag('td',Yii::t('churros', "Report totals "),
				[ 'class' => 'grid-group-total w1',
				  'colspan' => $colspan ] );
		}
		$nc = 0;
		$tdoptions = [ 'class' => 'w1' ];
		foreach( $this->columns as $kc => $column ) {
			if( $nc++ < $colspan ) {
				continue;
			}
			if( isset($summary_columns[$kc]) ) {
				$value = 0.0;
				if( $summary_columns[$kc] == 'f_avg' ) {
					if ($this->summaryValues[$kc][1] != 0 ) {
						$value = $this->summaryValues[$kc][0] / $this->summaryValues[$kc][1];
					}
				} else {
					$value = $this->summaryValues[$kc];
				}
				$ret .= Html::tag('td', $this->formatter->format(
						$value, $column->format),
						self::fetchColumnOptions($column, $tdoptions));
			} else {
				$ret .= Html::tag('td', '', $tdoptions);
			}
		}
		return $ret;
	}

	// copied from kartik data column
	static public function fetchColumnOptions($column, $options)
    {
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

	public function updateReportSummaries($model)
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
			case 'f_max':
				if( $this->summaryValues[$key] == null ) {
					$this->summaryValues[$key] = $model[$kc];
				} else if( $this->summaryValues[$key] < $model[$kc] ) {
					$this->summaryValues[$key] = $model[$kc];
				}
				break;
			case 'f_min':
				if( $this->summaryValues[$key] == null ) {
					$this->summaryValues[$key] = $model[$kc];
				} else if( $this->summaryValues[$key] > $model[$kc] ) {
					$this->summaryValues[$key] = $model[$kc];
				}
				break;
			case 'f_concat':
				$this->summaryValues[$key][] = $model[$kc];
				break;
			case 'f_distinct_concat':
				if (!in_array($model[$key], $this->summaryValues[$kc])) {
					$this->summaryValues[$key][] = $model[$kc];
				}
				break;
			}
		}
	}

}
