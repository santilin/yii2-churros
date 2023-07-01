<?php
/**
 * Just Another Grid Widget
 * https://dev.mysql.com/doc/refman/8.0/en/group-by-functions.html
 */
namespace santilin\churros\widgets\grid;

class ReportView extends SimpleGridView
{
	const PRE_SUMMARY_FUNCTIONS = [
		'' => [ 'None', true ],
		ReportView::F_COUNT => [ 'Count', true ],
		ReportView::F_SUM => [ 'Sum', true ],
    	ReportView::F_MAX => [ 'Max', true ],
		ReportView::F_MIN => [ 'Min', true ],
		ReportView::F_AVG => [ 'Average', true ],
		ReportView::F_CONCAT => [ 'Concatenate', false ],
		ReportView::F_DISTINCT_COUNT => [ 'Count (distinct)', false ],
		ReportView::F_DISTINCT_SUM => [ 'Sum (distinct)', false ],
		ReportView::F_DISTINCT_AVG => [ 'Avg (distinct)', false ],
		ReportView::F_DISTINCT_CONCAT => [ 'Concat (distinct)', false ],
	];


}
