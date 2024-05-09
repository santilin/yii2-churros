<?php
/**
 * Just Another Grid Widget
 * https://dev.mysql.com/doc/refman/8.0/en/group-by-functions.html
 */
namespace santilin\churros\widgets\grid;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\data\Pagination;
use yii\helpers\{ArrayHelper,Html,Url};
use yii\grid\DataColumn;
use santilin\churros\widgets\grid\GridGroup;
use santilin\churros\ChurrosAsset;

class SimpleGridView extends \yii\grid\GridView
{
	const SUMMARY_FUNCTIONS = [
		'count', 'sum', 'avg', 'concat', 'max', 'min', 'distinct_count', 'distinct_sum', 'distinct_avg', 'distinct_concat'
	];

    const FILTER_SELECT2 = 0;

	/**
	 * The groups headers and footers definitions
	 */
	public $groups = [];
	public $totalsRow = true;
	public $onlySummary = false;
	protected $summaryColumns = [];
	protected $summaryValues = [];
	protected $previousModel = null;
	protected $recno;
	protected $current_level = 0;

	public $itemLabelSingle = null;
	public $itemLabelPlural = null;
	public $itemLabelFew = null;
	public $itemLabelMany = null;
	public $itemLabelAccusative = null;
	public $grandTotalLabel = null;

    public $layout = "{summary}\n{pager}\n{items}";
    public $output = 'Screen';


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
		if( $this->output != 'Screen' ) {
			$config['dataProvider']->pagination = false;
		}
		if( empty($config['dataColumnClass']) ) {
			$config['dataColumnClass'] = \santilin\churros\widgets\grid\DataColumn::class;
		}
		// Eliminar propiedades de kartik data column
		foreach( $config['columns'] as &$column ) {
			if (is_array($column)) {
				unset($column['filterType'],$column['filterWidgetOptions']);
			}
		}
		parent::__construct($config);
	}

	public function init()
	{
		$this->initGroups(); // must be done before initColumns
		foreach ($this->columns as $kc => &$column) {
			if ($column === null) {
				unset($this->columns[$kc]);
			}
			if (is_array($column)) {
				unset($column['name']);
			}
			if (isset($this->groups[$kc]) && !$this->groups[$kc]->show_column??false) {
				$column['visible'] = false;
			}
			if (empty($column['summary']) && !empty($column['pageSummaryFunc'])) {
				$column['summary'] = $column['pageSummaryFunc'];
				unset($column['pageSummaryFunc']);
 			}
 			if (!empty($column['summary']) &&  !in_array($column['summary'], self::SUMMARY_FUNCTIONS)) {
				throw InvalidConfigException($column['summary'] . ': invalid summary function');
			}
		}
		parent::init();
		if (count($this->groups) != 0 || $this->totalsRow) {
			$this->beforeRow = function($model, $key, $index, $grid) {
				return $grid->groupHeader($model, $key, $index, $grid);
			};
			$this->afterRow = function($model, $key, $index, $grid) {
				if( $this->recno < $this->dataProvider->getCount() ) {
					return '';
				}
				return $grid->finalRow($model, $key, $index, $grid);
			};
		}
		$this->recno = 0;
		$this->initSummaryColumns();
		$this->resetGroupSummaryColumns();
		if ($this->onlySummary) {
			$this->dataProvider->pagination = false;
		}
	}

	public function run()
	{
		$view = $this->getView();
        ChurrosAsset::register($view);
        return parent::run();
	}

	/**
	 * Creates the constant array of summary columns to be used by the
	 * summary functions of each group
	 */
	protected function initSummaryColumns()
	{
		foreach ($this->columns as $kc => $column) {
			if (!property_exists($column, 'summary')) {
				continue;
			}
			if ($column->summary) {
				$kc = $column->attribute;
				$this->summaryColumns[$kc] = $column->summary;
				switch ($column->summary) {
					case 'sum':
					case 'count':
					case 'distinct_sum':
					case 'distinct_count':
						$this->summaryValues[$kc] = 0;
						break;
					case 'avg':
					case 'distinct_avg':
						$this->summaryValues[$kc][0] = 0;
						$this->summaryValues[$kc][1] = 0;
						break;
					case 'max':
						$this->summaryValues[$kc] = null;
						break;
					case 'min':
						$this->summaryValues[$kc] = null;
						break;
					case 'concat':
					case 'distinct_concat':
						$this->summaryValues[$kc] = [];
						break;
				}
			}
		}
	}

	// totales del informe, aparte de los totales de los grupos
	public function updateSummaryColumns($model)
	{
		// same in GridGroup::updateSummaries
		foreach( $this->summaryColumns as $kc => $summary) {
			switch( $summary ) {
			case 'sum':
				$this->summaryValues[$kc] += $model[$kc];
				break;
			case 'distinct_sum':
				if (!in_array($model[$kc], $this->summaryValues[$kc])) {
					$this->summaryValues[$kc] += $model[$kc];
				}
				break;
			case 'count':
				$this->summaryValues[$kc] ++;
				break;
			case 'distinct_count':
				if (!in_array($model[$kc], $this->summaryValues[$kc])) {
					$this->summaryValues[$kc] += $model[$kc];
				}
				break;
			case 'avg':
				$this->summaryValues[$kc][0] += $model[$kc];
				$this->summaryValues[$kc][1] ++;
				break;
			case 'distinct_avg':
				if (!in_array($model[$kc], $this->summaryValues[$kc])) {
					$this->summaryValues[$kc][0] += $model[$kc];
					$this->summaryValues[$kc][1] ++;
				}
				break;
			case 'max':
				if( $this->summaryValues[$kc] == null ) {
					$this->summaryValues[$kc] = $model[$kc];
				} else if( $this->summaryValues[$kc] < $model[$kc] ) {
					$this->summaryValues[$kc] = $model[$kc];
				}
				break;
			case 'min':
				if( $this->summaryValues[$kc] == null ) {
					$this->summaryValues[$kc] = $model[$kc];
				} else if( $this->summaryValues[$kc] > $model[$kc] ) {
					$this->summaryValues[$kc] = $model[$kc];
				}
				break;
			case 'concat':
				$this->summaryValues[$kc][] = $model[$kc];
				break;
			case 'distinct_concat':
				if (!in_array($model[$kc], $this->summaryValues[$kc])) {
					$this->summaryValues[$kc][] = $model[$kc];
				}
				break;
			}
		}
	}

	protected function resetGroupSummaryColumns()
	{
		foreach( $this->groups as $kg => $group )  {
			$group->resetSummaries($this->summaryColumns, 0, count($this->groups));
		}
	}

	protected function initGroups()
	{
		$this->current_level = 0; // details
		$level = 1;
 		$grid_orderby = [];
		foreach ($this->groups as $kg => $group_def) {
			$group = Yii::createObject(array_merge([
				'class' => GridGroup::className(),
				'grid' => $this,
				'column' => $kg,
			], $group_def));
            $group->level = $level++;
			$group->labels = (array)$group->labels;
			if (count($group->labels) >= 2) {
				list($group->header_label, $group->footer_label) = $group->labels;
			} else if (count($group->labels) == 1) {
				$group->header_label = $group->footer_label = $group->labels[0];
			}
			if ($group->value === null) {
				$group->value = $this->columns[$group->column]['value']??$this->columns[$group->column]['attribute']??$group->column;
			}
            $this->groups[$kg] = $group;
			if (!$group->orderby) {
				$group->orderby = $group->column;
			}
			$grid_orderby[] = $group->orderby;
        }
        if (count($grid_orderby)){
			$this->dataProvider->query->orderBy(join(',',$grid_orderby) . ',' . $this->dataProvider->query->orderBy);
		}
	}

	// override
	public function renderTableRow($model, $key, $index)
    {
		if( ($this->onlySummary && count($this->groups) == 0) || !$this->onlySummary ) {
			return parent::renderTableRow($model, $key, $index);
		} else {
			return null;
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
				if ($group->footer !== false) {
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
		$this->updateSummaryColumns($model);
		$updated_groups = [];
		foreach( $this->groups as $kg => $group ) {
			$updated_groups[$kg] = $group->updateGroup($model, $key, $index);
		}
		$first_header_shown = false;
		foreach( $this->groups as $kg => $group ) {
			if( $updated_groups[$kg] || $first_header_shown ) {
				$first_header_shown = true;
				if ($group->header!==false) {
					if( ($this->onlySummary && $group->level < count($this->groups))
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
		$tdoptions = [ 'colspan' => count($this->columns) ];
		$ret = '';
		foreach( array_reverse($this->groups) as $kg => $group ) {
			if( $group->footer ) {
				$ret .= Html::tag('tr',
					$group->getFooterContent($this->summaryColumns, $model, $key, $index, $tdoptions));
			}
		}
		if( $this->totalsRow ) {
			$fs = $this->getFooterSummary($this->summaryColumns, $tdoptions);
			if ($fs) {
				$ret .= Html::tag('tr', $fs, [ 'class' => 'grand-total']);
			}
		}
		return $ret;
	}

	/*
	 * Grand total
	 */
	public function getFooterSummary($summary_columns, $tdoptions)
	{
		if( count($summary_columns) == 0 ) {
			return '';
		}
		$p = $this->dataProvider->getPagination();
		if( $p && $p->getPageCount() > 1 ) {
			return '<td colspan="42">' . Yii::t('churros', 'Not showing totals because not all the rows have been shown') . '</td>';
		}
		$colspan = 0;
		foreach ($this->columns as $kc => $column) {
			if ($column instanceof DataColumn) {
				if (!array_key_exists($column->attribute?:$kc, $summary_columns)) {
					$colspan++;
				} else {
					break;
				}
			}
		}
		if ($colspan==0) {
			$ret = '</tr><tr>';
			$ret .= Html::tag('td', $this->grandTotalLabel?:Yii::t('churros', "Totals") . ' ',
				[ 'class' => 'total-label', 'colspan' => 42] );
			$ret .= '</tr><tr>';
		} else {
			$ret = Html::tag('td', $this->grandTotalLabel?:Yii::t('churros', "Totals") . ' ',
				[ 'class' => 'total-label', 'colspan' => $colspan ] );
		}
		$nc = 0;
		foreach ($this->columns as $column) {
			if( $nc++ < $colspan ) {
				continue;
			}
			$kc = $column->attribute;
			$classes = [];
			if( ($column->format?:'raw') != 'raw' ) {
				$classes[] = "format-{$column->format}";
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
						$value, $column->format), [ 'class' => join(' ', $classes) ]);
			} else {
				$ret .= Html::tag('td', '', [ 'class' => join(' ', $classes) ]);
			}
		}
		return $ret;
	}

}
