<?php
namespace santilin\churros\widgets\bs5;

use yii\helpers\{ArrayHelper,Html};
use yii\bootstrap5\ActiveForm as Bs5ActiveForm;
use santilin\churros\widgets\ActiveFormTrait;

// https://getbootstrap.com/docs/4.1/components/forms/

class ActiveForm extends Bs5ActiveForm
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
		'vertical' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => ['col-12', 'col-form-label text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => ['col-12', 'col-form-label text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2 offset-3',
					'label' => ['col-md-3 col-12', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-md-5 col-8'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'short' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-3 offset-3',
					'label' => ['col-lg-3 col-md-3 col-12', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-lg-3 col-sm-4 col-6'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => ['col-lg-3 col-md-4 col-6', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-lg-3 col-md-4 col-6'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group',
				]
			],

		],
		'1col' => [
			'full' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-12',
					'label' => ['col-12', 'col-form-label text-start'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-3',
					'label' => ['col-md-3 col-12', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2 offset-3',
					'label' => ['col-md-3 col-12', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-lg-3 col-md-5 col-8'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'short' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2 offset-3',
					'label' => ['col-lg-3 col-sm-3 col-12', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-lg-2 col-sm-9 col-6'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => '',
					'label' => ['col-lg-3 col-md-4 col-6', 'form-check-label text-start'],
					'wrapper' => ['offset-lg-3 offset-md-4 offset-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group form-check-input',
				]
			],

		],

		'2cols' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['col-12', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['col-md-3 col-12', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => ['col-lg-3 col-md-6 col-12', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-lg-4 col-md-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group',
				]
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => ['col-lg-3 col-md-4 col-6', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-lg-3 col-md-4 col-6'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => '',
					'label' => ['col-12', 'form-check-label text-start'],
					'wrapper' => ['offset-lg-3 offset-md-2 offset-0 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group form-check-input',
				]
			],
		],

		'3cols' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-12',
					'label' => ['col-12', 'col-form-label text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['col-md-3 col-12', 'col-form-label text-start text-md-end text-break'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['col-md-3 col-12', 'col-form-label text-start text-md-end text-break'],
					'wrapper' => ['col-lg-5 col-md-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['col-md-3 col-12', 'col-form-label text-start text-md-end text-break'],
					'wrapper' => ['col-lg-5 col-md-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => ['col-lg-3 col-md-4 col-6', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-lg-3 col-md-4 col-6'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group',
				]
			],

		],

		'4cols' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => ['col-12', 'col-form-label text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => ['col-12', 'col-form-label text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => ['control-label'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				],
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => ['col-12', 'col-form-label text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => ['col-lg-3 col-md-4 col-6', 'col-form-label text-start text-md-end'],
					'wrapper' => ['col-lg-3 col-md-4 col-6'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group',
				]
			],

		],
		'static' => [
			'horizontalCssClasses' => [
				'offset' => 'offset-md-2 offset-3',
				'label' => ['col-12', 'col-form-label text-start'],
				'wrapper' => ['col-12'],
				'error' => '',
				'hint' => '',
				'field' => '',
			]
		]
	];

}
