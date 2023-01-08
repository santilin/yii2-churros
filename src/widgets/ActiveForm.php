<?php
namespace santilin\churros\widgets;

use yii\helpers\{ArrayHelper,Html};
use yii\bootstrap4\ActiveForm as Bs4ActiveForm;
use santilin\churros\helpers\FormHelper;
use santilin\churros\widgets\ActiveFormTrait;

// https://getbootstrap.com/docs/4.1/components/forms/

class ActiveForm extends Bs4ActiveForm
{
	use ActiveFormTrait;

	const SHORT_FIELD_LAYOUT = [
		'horizontalCssClasses' => [
			'offset' => 'offset-sm-3',
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
			'offset' => ['col-lg-10 col-md-10 col-sm-9 col-xs-12', 'offset-lg-2 offset-md-2 offset-sm-3 offset-xs-0'],
			'label' => ['col-lg-2 col-md-2 col-sm-3 col-xs-0', 'col-form-label'],
			'wrapper' => 'col-lg-10 col-md-9 col-sm-9 col-xs-12',
			'error' => '',
			'hint' => '',
			'field' => 'form-group row'
		]
    ];


	public function layoutButtons($buttons)
	{
		switch($this->layout) {
		case '2cols':
			$classes = 'offset-md-2 col-sm-10';
			$ret = <<<html
<div class="form-group buttons"><div class="$classes">
html;
			$ret .= FormHelper::displayButtons($buttons);
			$ret .= <<<html
</div></div><!--buttons form-group-->
html;
			break;
		default:
// 		case '1col':
//      case 'horizontal':
// 		case 'inline':
			$classes = 'offset-md-2 col-sm-10';
			$ret = <<<html
<div class="form-group buttons"><div class="$classes">
html;
			$ret .= FormHelper::displayButtons($buttons);
			$ret .= <<<html
</div></div><!--buttons form-group-->
html;
			break;
		}
		return $ret;
	}

}
