<?php

namespace santilin\Churros\widgets;

use yii\grid\DataColumn;
use yii\helpers\Html;
use yii\helpers\Url;
/**
 */
class ExpandableTextColumn extends DataColumn
{
    /**
     * Maximun text length
     * @var
     */
    public $text_length = 100;

    /**
     * {@inheritdoc}
     * @throws \yii\base\InvalidArgumentException
     */
    protected function renderDataCellContent($model, $key, $index)
    {
		$text = $model->{$this->attribute};
		if (strlen($text)<=$this->text_length) {
			return $text;
		} else {
			/// @todo partir por el espacio más próximo
			$truncated_text = trim(substr($text, 0, $this->text_length));
			return Html::a($truncated_text . "&hellip;", "#collapse$key$index",
				[ 'class' => "fa fa-expand",  'data-toggle' =>'collapse',
				'role'=>'button', 'aria-expanded'=>'false', 'aria-controls'=>"collapse$key$index'"])
				. "<div class='collapse' id='collapse$key$index'><div class='card card-body'>".substr($text,$this->text_length). "</div></div>";
		}
    }

}
