<?php
/**
 * Just Another Grid Widget
 * https://dev.mysql.com/doc/refman/8.0/en/group-by-functions.html
 */
namespace santilin\churros\widgets\grid;
use yii;

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

	public function init()
    {
        if ($this->grandTotalLabel === null) {
            $this->grandTotalLabel = Yii::t('churros', 'Report totals');
        }
        parent::init();
	}

	static public function columnFormatOptions()
	{
		return [
			"raw" => Yii::t('churros', "As it is"),
			"label" => Yii::t('churros', 'Label'),
			"boolean" => Yii::t('churros', "Boolean"),
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
				"class:oneline" => Yii::t('churros', 'One line only'),
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

}
