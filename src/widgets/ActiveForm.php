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
			'layout' => 'short'
		]
	];
	const MEDIUM_FIELD_LAYOUT = [
		'horizontalCssClasses' => [
			'layout' => 'medium'
		]
	];
	const LONG_FIELD_LAYOUT = [
		'horizontalCssClasses' => [
			'layout' => 'long'
		]
	];


	const _SHORT_FIELD_LAYOUT = [
		'horizontalCssClasses' => [
			'offset' => 'offset-sm-2',
			'label' => 'col-md-2 col-sm-2 col-xs-12',
			'wrapper' => 'col-lg-2 col-md-2 col-sm-2 col-xs-4',
			'error' => '',
			'hint' => 'col-sm-2 col-xs-2',
			'field' => 'form-group row'
		]
	];
	const _MEDIUM_FIELD_LAYOUT = [
		'horizontalCssClasses' => [
			'offset' => 'col-sm-offset-3',
			'label' => 'col-md-3 col-sm-3 col-xs-12',
			'wrapper' => 'col-md-2 col-sm-2 col-xs-6',
			'error' => '',
			'hint' => 'col-sm-3 col-xs-3',
		]
	];

	public const FIELD_HORIZ_CLASSES = [
		'default' => [
			'1col_rows' => [
				'horizontalCssClasses' => [
					'offset' => ['col-lg-10 col-md-10 col-sm-9 col-xs-12', 'offset-lg-2 offset-md-2 offset-sm-3 offset-xs-0'],
					'label' => ['col-lg-2 col-md-2 col-sm-3 col-xs-0', 'col-form-label'],
					'wrapper' => 'col-lg-10 col-md-9 col-sm-9 col-xs-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'2cols_rows' => [
				'horizontalCssClasses' => [
					'offset' => 'col-sm-offset-3',
					'label' => 'col-sm-3',
					'wrapper' => 'col-sm-6',
					'error' => '',
					'hint' => 'col-sm-3',
					'field' => 'form-group row',
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




    public $fieldConfig = [
		'horizontalCssClasses' => [
			'offset' => '',
			'label' => '',
			'wrapper' => '',
			'error' => '',
			'hint' => '',
// 			'offset' => ['col-lg-10 col-md-10 col-sm-9 col-xs-12', 'offset-lg-2 offset-md-2 offset-sm-3 offset-xs-0'],
// 			'label' => ['col-lg-2 col-md-2 col-sm-3 col-xs-0', 'col-form-label'],
// 			'wrapper' => 'col-lg-10 col-md-9 col-sm-9 col-xs-12',
// 			'error' => '',
// 			'hint' => '',
// 			'field' => 'form-group row'
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
			&& empty($form_field->horizontalCssClasses['field'])
			&& empty($form_field->horizontalCssClasses['wrapper']) ) {
			$cssClasses = self::FIELD_HORIZ_CLASSES['default'][$row_layout]['horizontalCssClasses'];
		}
		if( count($cssClasses) ) {
            Html::addCssClass($form_fields[$form_field]->wrapperOptions, $cssClasses['wrapper']);
            Html::addCssClass($form_fields[$form_field]->labelOptions, $cssClasses['label']);
            Html::addCssClass($form_fields[$form_field]->errorOptions, $cssClasses['error']);
            Html::addCssClass($form_fields[$form_field]->hintOptions, $cssClasses['hint']);
		}
	}

}
