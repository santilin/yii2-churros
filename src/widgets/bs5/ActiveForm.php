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

	public const FIELD_HORIZ_CLASSES = [
		'1col' => [
			'full' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-12',
					'label' => ['col-12', 'col-form-label text-left'],
					'wrapper' => 'col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
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
					'wrapper' => 'col-xl-10 col-lg-10 col-md-10 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'short' => [
				'horizontalCssClasses' =>[
					'offset' => ['offset-xl-2 offset-lg-2 offset-md-2 offset-sm-3 offset-3'],
					'label' => ['col-xl-2 col-lg-2 col-md-2 col-sm-2 col-12', 'col-form-label text-left text-sm-left text-md-right text-lg-right'],
					'wrapper' => 'col-xl-10 col-lg-10 col-md-10 col-sm-10 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
		],

		'2cols' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => ['offset-xl-3 offset-lg-3 offset-md-3 offset-sm-3 offset-3'],
					'label' =>  ['col-xl-3 col-lg-3 col-md-3 col-sm-12 col-12', 'col-form-label text-left text-sm-left text-md-right text-lg-right'],
					'wrapper' => 'col-xl-9 col-lg-9 col-md-9 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
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
					'offset' => 'offset-xl-3 offset-lg-3 offset-md-3 offset-sm-4 offset-12',
					'label' => ['col-xl-3 col-lg-3 col-md-3 col-sm-4 col-12', 'col-form-label text-left text-sm-right text-md-right text-lg-right'],
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
			'full' => [
				'horizontalCssClasses' => [
					'offset' => ['offset-xl-12 offset-lg-12 offset-md-12 offset-sm-12 offset-12'],
					'label' => ['col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12', 'col-form-label text-left'],
					'wrapper' => 'col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
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
					'label' => ['col-xl-3 col-lg-3 col-md-3 col-sm-12 col-12',
								'col-form-label text-left text-sm-left text-md-right text-lg-right'],
					'wrapper' => 'kk col-xl-5 col-lg-5 col-md-6 col-sm-12 col-12',
					'error' => '',
					'hint' => '',
					'field' => 'form-group row',
				]
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => ['offset-lg-3 offset-md-3 offset-sm-3'],
					'label' => ['col-xl-3 col-lg-3 col-md-3 col-sm-12 col-12',
								'col-form-label text-left text-sm-left text-md-right text-lg-right'],
					'wrapper' => 'col-xl-5 col-lg-5 col-md-6 col-sm-12 col-12',
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
				$col = $col_sm = $col_md = $col_lg = $col_xl = 12;
				break;
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
		return "col col-$col col-sm-$col_sm col-md-$col_md col-lg-$col_lg col-xl-$col_xl my-1";
	}

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
		return $form_field_cfg;
	}

}
