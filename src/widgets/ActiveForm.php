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

	public const FIELD_HORIZ_CLASSES = [
		'1col' => [
			'large' => [
				'horizontalCssClasses' =>[
					'offset' => ['offset-xl-2 offset-lg-2 offset-md-2'],
					'label' => ['col-xl-2 col-lg-2 col-md-2 col-sm-12 col-12', 'col-form-label text-left text-sm-left text-md-right text-lg-right text-xl-right'],
					'wrapper' => 'col-xl-10 col-lg-10 col-md-10 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'medium' => [
				'horizontalCssClasses' =>[
					'offset' => ['offset-xl-2 offset-lg-2 offset-md-2 offset-sm-3 offset-3'],
					'label' => ['col-xl-2 col-lg-2 col-md-2 col-sm-12 col-12', 'col-form-label text-left text-sm-left text-md-right text-lg-right'],
					'wrapper' => 'col-lg-5 col-md-6 col-sm-7 col-8',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'short' => [
				'horizontalCssClasses' =>[
					'offset' => ['offset-lg-2 offset-md-2 offset-sm-3 offset-3'],
					'label' => ['col-lg-2 col-md-2 col-sm-12 col-12', 'col-form-label text-left text-sm-left text-md-right text-lg-right'],
					'wrapper' => 'col-lg-2 col-md-3 col-sm-4 col-6',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
		],

		'2cols' => [
			'large' => [
				'horizontalCssClasses' => [
					'offset' => ['offset-xl-3 offset-lg-3 offset-md-3 offset-sm-3 offset-3'],
					'label' =>  ['col-xl-3 col-lg-3 col-md-3 col-sm-12 col-12', 'col-form-label text-left text-sm-left text-md-right text-lg-right'],
					'wrapper' => 'col-xl-9 col-lg-9 col-md-9 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'medium' => [
				'horizontalCssClasses' =>[
					'offset' => ['col-lg-3 col-md-3 col-sm-4 col-12', 'offset-lg-2 offset-md-2 offset-sm-3'],
					'label' => ['col-lg-2 col-md-3 col-sm-4 col-12', 'col-form-label text-left text-sm-right text-md-right text-lg-right'],
					'wrapper' => 'col-lg-5 col-md-6 col-sm-7 col-8',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => ['col-lg-3 col-md-6 col-sm-9 col-12', 'offset-lg-2 offset-md-2 offset-sm-3'],
					'label' => ['col-lg-3 col-md-6 col-sm-12 col-12', 'col-form-label text-left text-sm-left text-md-right text-lg-right'],
					'wrapper' => 'col-lg-6 col-lg-6 col-md-6 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
		],

		'3cols' => [
			'large' => [
				'horizontalCssClasses' => [
					'offset' => ['offset-lg-3 offset-md-3 offset-sm-3'],
					'label' => ['col-lg-3 col-md-3 col-sm-12 col-12', 'col-form-label text-left text-sm-left text-md-right text-lg-right'],
					'wrapper' => 'col-lg-9 col-md-9 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'medium' => [
				'horizontalCssClasses' => [
					'offset' => ['offset-lg-3 offset-md-3 offset-sm-3'],
					'label' => ['col-lg-3 col-md-3 col-sm-12 col-12', 'col-form-label text-left text-sm-left text-md-right text-lg-right'],
					'wrapper' => 'col-lg-9 col-md-9 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => ['offset-lg-3 offset-md-3 offset-sm-3'],
					'label' => ['kk col-lg-3 col-md-3 col-sm-12 col-12',
								'col-form-label text-left text-sm-left text-md-right text-lg-right'],
					'wrapper' => 'col-lg-9 col-md-9 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
		],

		'4cols' => [
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

	protected function fieldClasses(string $row_layout, string $fld_layout = 'large'): array
	{
		$form_field_cfg = self::FIELD_HORIZ_CLASSES[$row_layout][$fld_layout];
		if (YII_ENV_DEV) {
			if (is_array($form_field_cfg['horizontalCssClasses']['wrapper'])) {
				$form_field_cfg['horizontalCssClasses']['wrapper'] = array_merge(
					[ "wrapper row-$row_layout fld-$fld_layout"],
					$form_field_cfg['horizontalCssClasses']['wrapper']);
			} else {
				$form_field_cfg['horizontalCssClasses']['wrapper'] = "wrapper row-$row_layout fld-$fld_layout "
					. $form_field_cfg['horizontalCssClasses']['wrapper'];
			}
			if (is_array($form_field_cfg['horizontalCssClasses']['label'])) {
				$form_field_cfg['horizontalCssClasses']['label'] = array_merge(
					[ "label row-$row_layout fld-$fld_layout"],
 					$form_field_cfg['horizontalCssClasses']['label']);
			} else {
				$form_field_cfg['horizontalCssClasses']['label'] = "label row-$row_layout fld-$fld_layout "
					. $form_field_cfg['horizontalCssClasses']['label'];
			}
			if (is_array($form_field_cfg['horizontalCssClasses']['offset'])) {
				$form_field_cfg['horizontalCssClasses']['offset'] = array_merge(
					[ "offset row-$row_layout fld-$fld_layout"],
					$form_field_cfg['horizontalCssClasses']['offset']);
			} else {
				$form_field_cfg['horizontalCssClasses']['offset'] = "offset row-$row_layout fld-$fld_layout "
					. $form_field_cfg['horizontalCssClasses']['offset'];
			}
		}
// 		Html::addCssClass($form_field_cfg['horizontalCssClasses']['wrapperOptions'], $cssClasses['wrapper']);
// 		Html::addCssClass($form_field_cfg['labelOptions'], $cssClasses['label']);
// 		Html::addCssClass($form_field_cfg['errorOptions'], $cssClasses['error']);
// 		Html::addCssClass($form_field_cfg['hintOptions'], $cssClasses['hint']);
// 		Html::addCssClass($form_field_cfg['options'], $cssClasses['field']);

		return $form_field_cfg;
	}

}
