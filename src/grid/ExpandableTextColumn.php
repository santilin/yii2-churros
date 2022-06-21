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
    public $length = 30;
    public $format = 'text';

    /**
     * {@inheritdoc}
     * @todo Place the hellip instead of an space
     * @throws \yii\base\InvalidArgumentException
     */
    protected function renderDataCellContent($model, $key, $index)
    {
		$text = $model->{$this->attribute};
		if( $this->format == 'html' ) {
			$text = html_entity_decode(strip_tags($text));
		}
		if( $this->length == 0 || strlen($text)<=$this->length) {
			return $text;
		} else {
			/// @todo partir por el espacio más próximo
			$truncated_text = trim(mb_substr($text, 0, $this->length));
			return Html::a($truncated_text,
				"#collapse$key$index{$this->attribute}",
				[ 'class' => "fa fa-expand",  'data-toggle' =>'collapse', 'role'=>'button',
				  'aria-expanded'=>'false', 'aria-controls'=>"collapse$key$index{$this->attribute}"]
				) . "<div class='collapse' id='collapse$key$index{$this->attribute}'><div class='card card-body' style='display:inline'>".mb_substr($text,$this->length). "</div></div>";
		}
    }

}
