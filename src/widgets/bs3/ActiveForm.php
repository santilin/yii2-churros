<?php
namespace santilin\churros\widgets\bs3;

use yii\helpers\Html;
use yii\bootstrap\ActiveForm as Bs3ActiveForm;
use santilin\churros\helpers\FormHelper;

// https://getbootstrap.com/docs/3.4/css/#forms
// https://getbootstrap.com/docs/3.4/css/#grid

class ActiveForm extends Bs3ActiveForm
{
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

	public function layoutFields($form_fields)
	{
		$ret = '';
		if ($this->layout == "horizontal" || $this->layout == '1col' || $this->layout == "inline" ) {
			foreach( $form_fields as $name => $code ) {
				$ret .= $form_fields[$name]. "\n";
			}
		} else if( count($form_layout_rows) ) {
			// Check if some fields have been removed after setting the layout
			foreach($form_layout_rows as $lrowkey => $lrow ) {
				foreach( $lrow as $ffkey => $ff ) {
					if( $form_fields[$ff] === false ) {
						unset( $form_layout_rows[$lrowkey][$ffkey] );
					}
				}
			}
			foreach($form_layout_rows as $lrow ) {
				switch(count($lrow)) {
				case 1:
					$ret .= '<div class="row">';
					$ret .= '<div class="col-sm-12">';
					$ret .= $form_fields[$lrow[0]];
					$ret .= '</div>';
					$ret .= '</div>';
					break;
				case 2:
					$ret .= '<div class="row">';
					$ret .= '<div class="col-sm-6">';
					$ret .= $form_fields[$lrow[0]];
					$ret .= '</div>';
					$ret .= '<div class="col-sm-6">';
					$ret .= $form_fields[$lrow[1]];
					$ret .= '</div>';
					$ret .= '</div>';
					break;
				case 3:
					$ret .= '<div class="row">';
					$ret .= '<div class="col-sm-4">';
					$ret .= $form_fields[$lrow[0]];
					$ret .= '</div>';
					$ret .= '<div class="col-sm-4">';
					$ret .= $form_fields[$lrow[1]];
					$ret .= '</div>';
					$ret .= '<div class="col-sm-4">';
					$ret .= $form_fields[$lrow[2]];
					$ret .= '</div>';
					$ret .= '</div>';
					break;
				case 4:
					$ret .= '<div class="row">';
					$ret .= $form_fields[$lrow[0]];
					$ret .= $form_fields[$lrow[1]];
					$ret .= $form_fields[$lrow[2]];
					$ret .= $form_fields[$lrow[3]];
					$ret .= '</div>';
					break;
				}
			}
		}
		return $ret;
	}

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
