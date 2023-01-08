<?php
namespace santilin\churros\widgets\bs3;

use yii\helpers\Html;
use yii\bootstrap\ActiveForm as Bs3ActiveForm;
use santilin\churros\helpers\FormHelper;
use santilin\churros\widgets\ActiveFormTrait;

// https://getbootstrap.com/docs/3.4/css/#forms
// https://getbootstrap.com/docs/3.4/css/#grid

class ActiveForm extends Bs3ActiveForm
{
	use ActiveFormTrait;

	const SHORT_FIELD_LAYOUT = [
		'horizontalCssClasses' => [
			'offset' => 'col-sm-offset-3',
			'label' => 'col-md-3 col-sm-3 col-xs-12',
			'wrapper' => 'col-lg-2 col-md-2 col-sm-2 col-xs-4',
			'error' => '',
			'hint' => 'col-sm-3 col-xs-3',
		]
	];
	const MEDIUM_FIELD_LAYOUT = [
		'horizontalCssClasses' => [
			'offset' => 'col-sm-offset-3',
			'label' => 'col-md-3 col-sm-3 col-xs-12',
			'wrapper' => 'col-md-2 col-sm-2 col-xs-6',
			'error' => '',
			'hint' => 'col-sm-3 col-xs-3',
		]
	];
    public $fieldConfig = [
		'horizontalCssClasses' => [
			'offset' => 'col-sm-offset-3',
			'label' => 'col-sm-3 col-md-3 col-xs-12',
			'wrapper' => 'col-sm-6 col-md-6 col-xs-12',
			'error' => '',
			'hint' => 'col-sm-3 col-xs-3',
		]
    ];


	public function layoutButtons($buttons)
	{
		switch($this->layout) {
		case '2cols':
			$classes = 'col-md-offset-3 col-sm-9';
			$ret = <<<html
<div class="form-group buttons"><div class="$classes">
html;
			$ret .= FormHelper::displayButtons($buttons);
			$ret .= <<<html
</div></div><!--buttons form-group-->
html;
		default:
// 		case 'horizontal':
// 		case 'inline':
// 		case '1col':
			$classes = 'col-md-offset-3 col-sm-9';
			$ret = <<<html
<div class="form-group buttons"><div class="$classes">
html;
			$ret .= FormHelper::displayButtons($buttons);
			$ret .= <<<html
</div></div><!--buttons form-group-->
html;
			break;
			break;
		}
		return $ret;
	}

}
