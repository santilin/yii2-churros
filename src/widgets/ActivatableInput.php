<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;

/**
 */

class ActivatableInput extends \kartik\datecontrol\DateControl
{
	public $labels = [];

    /**
     * {@inheritdoc}
     */
    public function run()
    {
		if( $this->labels == [] ) {
			$this->labels = [ Yii::t('churros', 'Inactive'), Yii::t('churros', 'Active') ];
		}
		if( !isset($this->widgetOptions['layout'])) {
			$value = Html::getAttributeValue($this->model, $this->attribute);
			if( $value == '' ) {
				$active = $this->labels[1];
			} else {
				$active = Yii::t('churros', '{inactive} since',
					[ 'inactive' => $this->labels[0] ]);
			}
			$layout = <<< HTML
<div class="input-group-addon"><span class="input-group-text">$active</span></div>
{input}
{picker}
{remove}
HTML;
			$this->widgetOptions['layout'] = $layout;
		}
		echo parent::run();
	}
}

