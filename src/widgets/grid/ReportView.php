<?php
/**
 * Just Another Grid Widget
 * https://dev.mysql.com/doc/refman/8.0/en/group-by-functions.html
 */
namespace santilin\churros\widgets\grid;

class ReportView extends SimpleGridView
{
	const PRE_SUMMARY_FUNCTIONS = [
		'' => 'None',
		ReportView::F_COUNT => 'Count',
		ReportView::F_SUM => 'Sum',
    	ReportView::F_MAX => 'Max',
		ReportView::F_MIN => 'Min',
		ReportView::F_AVG => 'Average',
		ReportView::F_CONCAT => 'Concatenate',
		ReportView::F_DISTINCT_COUNT => 'Count (distinct)',
		ReportView::F_DISTINCT_SUM => 'Sum (distinct)',
		ReportView::F_DISTINCT_AVG => 'Avg (distinct)',
		ReportView::F_DISTINCT_CONCAT => 'Concat (distinct)'
	];


}
