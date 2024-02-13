<?php
namespace santilin\churros\widgets;

use yii\helpers\{ArrayHelper,Html};
use yii\bootstrap4\ActiveForm as Bs4ActiveForm;
use santilin\churros\widgets\ActiveFormTrait;

// https://getbootstrap.com/docs/4.1/components/forms/

class ActiveForm extends Bs4ActiveForm
{
	use ActiveFormTrait;

//     public $fieldConfig = [
// 		'horizontalCssClasses' => [
// 			'offset' => '',
// 			'label' => '',
// 			'wrapper' => '',
// 			'error' => '',
// 			'hint' => '',
// 		]
//     ];
//
	public $errorSummaryCssClass = 'error-summary alert alert-danger';

	public const FORM_FIELD_HORIZ_CLASSES = [
		'1col' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-12',
					'label' => ['col-12 col-form-label text-left'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'large' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2',
					'label' => ['col-md-2 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-md-10 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'medium' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2 offset-3',
					'label' => ['col-md-2 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-md-10 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'short' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2 offset-3',
					'label' => ['col-sm-2 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-sm-10 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
		],

		'2cols' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' =>  ['col-md-3 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' =>  ['col-md-3 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'medium' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-3 offset-sm-4 offset-12',
					'label' => ['col-md-3 col-sm-4 col-12', 'col-form-label text-left text-sm-right'],
					'wrapper' => ['col-lg-5 col-md-6 col-sm-7 col-8'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => ['col-lg-3 col-md-6 col-sm-12 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-md-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
		],

		'3cols' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-12',
					'label' => ['col-12', 'col-form-label text-left'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['col-md-3 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'medium' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['col-md-3 col-12',
								'col-form-label text-left text-md-right'],
					'wrapper' => ['col-lg-5 col-md-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-lg-3 offset-md-3 offset-sm-3',
					'label' => ['col-md-3 col-12',
								'col-form-label text-left text-md-right'],
					'wrapper' => ['col-lg-5 col-md-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
		],

		'4cols' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => ['col-lg-10 col-md-10 col-sm-9 col-12', 'offset-lg-2 offset-md-2 offset-sm-3'],
					'label' => ['col-lg-12 col-md-12 col-sm-12 col-12', 'col-form-label text-left'],
					'wrapper' => 'col-lg-12 col-md-12 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => ['col-lg-10 col-md-10 col-sm-9 col-12', 'offset-lg-2 offset-md-2 offset-sm-3'],
					'label' => ['col-lg-12 col-md-12 col-sm-12 col-12', 'col-form-label text-left'],
					'wrapper' => 'col-lg-12 col-md-12 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'medium' => [
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
					'offset' => ['col-lg-10 col-md-10 col-sm-9 col-12', 'offset-lg-2 offset-md-2 offset-sm-3'],
					'label' => ['col-lg-12 col-md-12 col-sm-12 col-12', 'col-form-label text-left'],
					'wrapper' => 'col-lg-12 col-md-12 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			]
		],
		'static' => [
			'horizontalCssClasses' => [
				'offset' => ['col-lg-10 col-md-10 col-sm-9 col-12', 'offset-lg-2 offset-md-2 offset-sm-3'],
				'label' => ['col-lg-12 col-md-12 col-sm-12 col-12', 'col-form-label text-left'],
				'wrapper' => 'col-lg-12 col-md-12 col-sm-12 col-12',
				'error' => '',
				'hint' => '',
				'field' => 'form-group row',
			]
		]
	];

	public function columnClasses(int $cols): string
	{
		switch ($cols) {
			case 1:
				return "col col-12";
			case 2:
				$col = $col_sm = 12;
				$col_md = $col_lg = $col_xl = 6;
				break;
			case 3:
				$col = $col_sm = 4;
				$col_md = $col_lg = $col_xl = 4;
				break;
			case 4:
			default:
				$col = $col_sm = 3;
				$col_md = $col_lg = $col_xl = 3;
		}
		return "col col-$col col-sm-$col_sm col-md-$col_md col-lg-$col_lg col-xl-$col_xl";
	}

}
