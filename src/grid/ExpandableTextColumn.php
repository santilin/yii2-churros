<?php

namespace santilin\churros\grid;

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
    public $text_length = 30;

    /**
     * {@inheritdoc}
     * @todo Place the hellip instead of an space
     * @throws \yii\base\InvalidArgumentException
     */
    protected function renderDataCellContent($model, $key, $index)
    {
		$text = $model->{$this->attribute};
		if (mb_strlen($text)<=$this->text_length) {
			return $text;
		} else {
			/// @todo partir por el espacio más próximo
			$truncated_text = trim(mb_substr($text, 0, $this->text_length));
			return Html::a($truncated_text . "&hellip;",
				"#collapse$key$index{$this->attribute}",
				[ 'class' => "fa fa-expand",  'data-toggle' =>'collapse', 'role'=>'button',
				  'aria-expanded'=>'false', 'aria-controls'=>"collapse$key$index{$this->attribute}"]
				);
// 				. "<div class='collapse' id='collapse$key$index{$this->attribute}'><div class='card card-body'>".substr($text,$this->text_length). "</div></div>";
		}
    }

}
