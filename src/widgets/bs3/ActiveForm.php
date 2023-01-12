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

	public const FIELD_HORIZ_CLASSES = [
		'default' => [
			'1col_rows' => [
				'horizontalCssClasses' => [
					'offset' => 'col-xs-offset-3 col-sm-offset-3 col-md-offset-3 col-lg-offset-3',
					'label' => 'col-xs-12 col-sm-3 col-md-3 col-lg-3',
					'wrapper' => 'col-xs-12 col-sm-6 col-md-6 col-lg-6',
					'error' => '',
					'hint' => 'col-xs-0 col-sm-3 col-md-3 col-lg-3',
				]
			],
			'2cols_rows' => [
				'horizontalCssClasses' => [
					'offset' => 'col-sm-offset-3',
					'label' => 'col-sm-3',
					'wrapper' => 'col-sm-6',
					'error' => '',
					'hint' => 'col-sm-3',
				]
			],
			'3cols_rows' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-9',
				]
			],
			'4cols_rows' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-12 text-left',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-6',
					'error' => '',
					'hint' => '',

				]
			],
		],
		'large' => [
			'1col_rows' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-1 one-column-row',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-11',
				]
			],
			'2cols_rows' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-9',
				]
			],
			'3cols_rows' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-9',
				]
			],
			'4cols_rows' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-9',
				]
			],
		],
		'medium' => [
			'default' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-1 one-column-row',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-6',
				]
			],
			'2cols' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-2',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-7',
				]
			],
			'3cols' => [
				'horizontalCssClasses' => [
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-8',
				]
			],
			'4cols' => [
				'horizontalCssClasses' => [
					'label' => 'control-label',
					'error' => 'col_sm_12',
					'hint' => 'col_sm_12',
					'wrapper' => '',
				],
				'options' => [ 'class' => 'control-group col-sm-2' ],
			],
			'short' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'wrapper' => '',
				],
				'options' => [ 'class' => 'control-group col-sm-2' ],
			],
		],
		'short' => [
			'1col' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'wrapper' => 'col-sm-2',
				]
			],
			'2cols' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'wrapper' => 'col-sm-3',
				]
			],
			'3cols' => [
				'horizontalCssClasses' => [
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-3',
				]
			],
			'4cols_rows' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'wrapper' => '',
				],
				'options' => [ 'class' => 'control-group col-sm-2' ],
			],
		],
	];


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
			'offset' => '',
			'label' => '',
			'wrapper' => '',
			'error' => '',
			'hint' => '',
// 			'offset' => 'col-sm-offset-3',
// 			'label' => 'col-sm-3 col-md-3 col-xs-12',
// 			'wrapper' => 'col-sm-6 col-md-6 col-xs-12',
// 			'error' => '',
// 			'hint' => 'col-sm-3 col-xs-3',
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

	protected function setFieldClasses(array &$form_fields, string $form_field, string $row_layout)
	{
		$cssClasses = [];
		$classes_set = false;
		if( !empty($form_field->horizontalCssClasses['layout']) ) {
			$field_layout = $form_field->horizontalCssClasses['layout'];
			$cssClasses = self::FIELD_HORIZ_CLASSES[$field_layout][$row_layout]['horizontalCssClasses'];
		} else if( empty($form_field->horizontalCssClasses['offset'])
			&& empty($form_field->horizontalCssClasses['label'])
			&& empty($form_field->horizontalCssClasses['hint'])
			&& empty($form_field->horizontalCssClasses['error'])
			&& empty($form_field->horizontalCssClasses['wrapper']) ) {
			$cssClasses = self::FIELD_HORIZ_CLASSES['default'][$row_layout]['horizontalCssClasses'];
		}
		if( count($cssClasses) ) {
            $form_fields[$form_field]->wrapperOptions = ['class' => $cssClasses['wrapper']];
            $form_fields[$form_field]->labelOptions = ['class' => 'control-label ' . $cssClasses['label']];
            $form_fields[$form_field]->errorOptions['class'] = 'help-block help-block-error ' . $cssClasses['error'];
            $form_fields[$form_field]->hintOptions['class'] = 'help-block ' . $cssClasses['hint'];
		}
	}

}
