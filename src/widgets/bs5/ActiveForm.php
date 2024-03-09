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
		'1col' => [
			'full' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-12',
					'label' => ['g-0 col-12', 'col-form-label text-left'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-2',
					'label' => ['g-0 col-md-3 col-12', 'col-form-label text-nowrap text-left text-md-right'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2 offset-3',
					'label' => ['g-0 col-md-3 col-12', 'col-form-label text-nowrap  text-left text-md-right'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'short' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2 offset-3',
					'label' => ['g-0 col-md-3 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-lg-2 col-md-3 col-sm-2 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
		],

		'2cols' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['g-0 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['g-0 col-md-3 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => ['g-0 col-lg-3 col-md-6 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-lg-4 col-md-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group',
				]
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => ['g-0 col-lg-3 col-md-4 col-6', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-lg-3 col-md-4 col-6'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => ['g-0 col-lg-3 col-md-4 col-6', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-lg-3 col-md-4 col-6'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group',
				]
			],
		],

		'3cols' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-12',
					'label' => ['g-0 col-12', 'col-form-label text-left'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['g-0 col-md-3 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['g-0 col-md-3 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-lg-5 col-md-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => ['g-0 col-md-3 col-12', 'col-form-label text-left text-md-right'],
					'wrapper' => ['col-lg-5 col-md-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
		],

		'4cols' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => ['g-0 col-12', 'col-form-label text-left'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => ['g-0 col-12', 'col-form-label text-left'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => ['g-0 control-label'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				],
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => ['g-0 col-12', 'col-form-label text-left'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			]
		],
		'static' => [
			'horizontalCssClasses' => [
				'offset' => 'offset-md-2 offset-3',
				'label' => ['g-0 col-12', 'col-form-label text-left'],
				'wrapper' => ['col-12'],
				'error' => '',
				'hint' => '',
				'field' => '',
			]
		]
	];

}
