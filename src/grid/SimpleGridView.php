<?php
/**
 * Just Another Grid Widget
 * https://dev.mysql.com/doc/refman/8.0/en/group-by-functions.html
 */
namespace santilin\churros\grid;

use Yii;
use yii\helpers\{ArrayHelper,Html,Url	};
use santilin\churros\grid\GridGroup;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\data\Pagination;

class SimpleGridView extends \yii\grid\GridView
{
    const F_COUNT = 'f_count';
    const F_SUM = 'f_sum';
    const F_MAX = 'f_max';
    const F_MIN = 'f_min';
    const F_AVG = 'f_avg';
    const F_CONCAT = 'f_concat';

    const FILTER_SELECT2 = 0;

	/**
	 * The groups headers and footers definitions
	 */
	public $groups = [];
	public $totalsRow = true;
	public $bsVersion = '3.x';
	public $onlySummary = false;
	public $itemLabelSingle = '';
	public $itemLabelPlural = '';

	protected $summaryColumns = [];
	protected $summaryValues = [];
	protected $previousModel = null;
	protected $recno;
	protected $current_level = 0;
    public $layout = "{summary}\n{pager}\n{items}";
    public $output = 'Screen';


	public function __construct($config = [])
	{
		if( empty($this->dataColumnClass ) ) {
			$this->dataColumnClass = ReportDataColumn::class;
		}
		if (empty($config['pager']) ) {
			$config['pager'] = [
				'firstPageLabel' => '<<',
				'lastPageLabel' => '>>',
				'nextPageLabel' => '>',
				'prevPageLabel' => '<',
			];
		}
		if( !$this->output == 'Screen' ) {
			$config['dataProvider']->pagination = false;
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
		$this->resetGroupSummaryColumns();
		if ($this->onlySummary) {
			$this->dataProvider->pagination = false;
		}
	}

	static public function columnFormatOptions()
	{
		return [
			"raw" => Yii::t('churros', "Short text"),
			"boolean" => Yii::t('churros', "Booleano"),
			Yii::t('churros', "Numbers") => [
				"integer" => Yii::t('churros', "Integer"),
				"decimal" => Yii::t('churros', "Decimal"),
				"percent" => Yii::t('churros', "Percentage"),
				"scientific" => Yii::t('churros', "Scientific notation"),
				"currency" => Yii::t('churros', "Currency"),
				"spellOut" => Yii::t('churros', "Spelled out number"),
				"ordinal" => Yii::t('churros', "Ordinal"),
				"shortSize" => Yii::t('churros', "Human readable size"),
				"size" => Yii::t('churros', "Long human readable size"),
				"shortLength" => Yii::t('churros', "Length"),
				"length" => Yii::t('churros', "Long length"),
				"shortWeigth" => Yii::t('churros', "Weight"),
				"weight" => Yii::t('churros', "Long weight"),
			],
			Yii::t('churros', "Dates & time") => [
				"date" => Yii::t('churros', "Date"),
				"time" => Yii::t('churros', "Time"),
				"dateTime" => Yii::t('churros', "Date & time"),
				"timestamp" => Yii::t('churros', "Time stamp"),
				"duration" => Yii::t('churros', "Duration"),
				"hours" => Yii::t('churros', "Hours as decimal"),
			],
			Yii::t('churros', "Text") => [
				"tokenized" => Yii::t('churros', "Tokenized by ','"),
				"truncatedText" => Yii::t('churros', "Truncated text"),
				"html" => Yii::t('churros', "Html text"),
				"ntext" => Yii::t('churros', "Text with newlines"),
				"paragraphs" => Yii::t('churros', "Text with paragraphs"),
			],
			Yii::t('churros', "Images") => [
				"image" => Yii::t('churros', "Image"),
			],
			Yii::t('churros', "Internet") => [
				"email" => Yii::t('churros', "Email"),
				"url" => Yii::t('churros', "Url"),
			],
			Yii::t('churros', "Others") => [
				"phoneNumber" => Yii::t('churros', "Phone number"),
			],
		];
	}


	/**
	 * Creates the constant array of summary columns to be used by the
	 * summary functions of each group
	 */
	protected function initSummaryColumns()
	{
		foreach( $this->columns as $column ) {
			if( !empty($column->pageSummaryFunc) ) {
				$kc = $column->attribute;
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

	public function updateSummaryColumns($model)
	{
		// same in GridGroup::updateSummaries
		foreach( $this->summaryColumns as $kc => $summary) {
			switch( $summary ) {
			case 'f_sum':
				$this->summaryValues[$kc] += $model[$kc];
				break;
			case 'f_count':
				$this->summaryValues[$kc] ++;
				break;
			case 'f_avg':
				$this->summaryValues[$kc][0] += $model[$kc];
				$this->summaryValues[$kc][1] ++;
				break;
			case 'f_max':
				if( $this->summaryValues[$kc] == null ) {
					$this->summaryValues[$kc] = $model[$kc];
				} else if( $this->summaryValues[$kc] < $model[$kc] ) {
					$this->summaryValues[$kc] = $model[$kc];
				}
				break;
			case 'f_min':
				if( $this->summaryValues[$kc] == null ) {
					$this->summaryValues[$kc] = $model[$kc];
				} else if( $this->summaryValues[$kc] > $model[$kc] ) {
					$this->summaryValues[$kc] = $model[$kc];
				}
				break;
			case 'f_concat':
				$this->summaryValues[$kc][] = $model[$kc];
				break;
			case 'f_distinct_concat':
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
            $group->footer = true;
            $this->groups[$kg] = $group;
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
		$this->updateSummaryColumns($model);
		$updated_groups = [];
		foreach( $this->groups as $kg => $group ) {
			$updated_groups[$kg] = $group->updateGroup($model, $key, $index);
		}
		$first_header_shown = false;
		foreach( $this->groups as $kg => $group ) {
			if( $updated_groups[$kg] || $first_header_shown ) {
				$first_header_shown = true;
				if( $group->header ) {
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
				[ 'class' => 'reportview-grand-total']);
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
			return '<td colspan="42">No muestro totales porque no se están mostrando todos los registros.</tr>';
		}
		$colspan = 0;
		foreach( $this->columns as $kc => $column ) {
			if( !isset($summary_columns[$column->attribute]) ) {
				$colspan++;
			} else {
				break;
			}
		}
		if( $colspan==0) {
			$ret = '</tr><tr>';
			$ret .= Html::tag('td', Yii::t('churros', "Report totals") . ' ', [
				'class' => 'reportview-total-label', 'colspan' => 42] );
			$ret .= '</tr><tr>';
		} else {
			$ret = Html::tag('td',Yii::t('churros', "Report totals") . ' ',
				[ 'class' => 'reportview-total-label', 'colspan' => $colspan ] );
		}
		$nc = 0;
		foreach( $this->columns as $column ) {
			if( $nc++ < $colspan ) {
				continue;
			}
			$kc = $column->attribute;
			$classes = [
				'w1'
			];
			if( ($column->format?:'raw') != 'raw' ) {
				$classes[] = "reportview-{$column->format}";
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
