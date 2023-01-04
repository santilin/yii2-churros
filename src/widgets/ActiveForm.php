<?php
namespace santilin\churros\widgets;

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm as Bs4ActiveForm;
use santilin\churros\helpers\FormHelper;

class ActiveForm extends Bs4ActiveForm
{

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
