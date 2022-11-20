<?php

namespace santilin\churros\grid;

use yii\grid\DataColumn;

class ReportDataColumn extends DataColumn
{
	public $width;
	public $pageSummary;
	public $pageSummaryFunc;
	public $columnSummaryFunc;
	public $hAlign;
	public $vAlign;
	public $hidden;
	public $noWrap;
	public $filterType;
	public $filterWidgetOptions;
}



