<?php
namespace santilin\churros\widgets;

use Yii;
use yii\helpers\{ArrayHelper,Html};
use yii\bootstrap5\ActiveForm as Bs5ActiveForm;
use santilin\churros\widgets\ActiveFormTrait;

// https://getbootstrap.com/docs/4.1/components/forms/

class ActiveForm extends Bs5ActiveForm
{
	use ActiveFormTrait;

	public $fieldConfig = [ 'template' => "{label}\n{beginWrapper}\n{input}\n{hint}\n{error}\n{endWrapper}", ];

	public $errorSummaryCssClass = 'error-summary alert alert-danger';
	public $warningSummaryCssClass = 'error-summary alert alert-warning';

	public const FORM_FIELD_HORIZ_CLASSES = [
		'vertical' => [ // form_layout
			'full' => [ // field_layout
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => [ 'col-form-label col-12', 'text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => [ 'col-form-label col-12', 'text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2 offset-3',
					'label' => [ 'col-form-label col-12', 'text-start'],
					'wrapper' => ['col-md-5 col-8'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'short' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-3 offset-3',
					'label' => [ 'col-form-label col-12', 'text-start'],
					'wrapper' => ['col-lg-3 col-sm-4 col-6'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => [ 'col-form-label col-12', 'text-start'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group',
				]
			],
		],
		'2cols-vertical' => [
			'full' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => [ 'col-form-label col-12', 'text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => [ 'col-form-label col-12', 'text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2 offset-3',
					'label' => [ 'col-form-label col-12', 'text-start'],
					'wrapper' => ['col-md-5 col-8'],
					'error' => '',
					'hint' => 'col-12',
					'field' => ['mi-clase'],
				]
			],
			'short' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-3 offset-3',
					'label' => [ 'col-form-label col-12', 'text-start'],
					'wrapper' => ['col-lg-3 col-sm-4 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => [ 'col-form-label col-12', 'text-start'],
					'wrapper' => ['col-6'],
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
					'label' => [ 'col-form-label col-12', 'text-start'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-3',
					'label' => [ 'col-form-label col-md-3 col-12', 'text-start text-md-end'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2 offset-3',
					'label' => [ 'col-form-label col-md-3 col-12', 'text-start text-md-end'],
					'wrapper' => ['col-lg-4 col-md-6 col-8'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'short' => [
				'horizontalCssClasses' =>[
					'offset' => 'offset-md-2 offset-3',
					'label' => [ 'col-form-label col-lg-3 col-sm-3 col-12', 'text-start text-md-end'],
					'wrapper' => ['col-lg-2 col-sm-9 col-6'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => '',
					'label' => [ 'col-form-label col-lg-3 col-md-4 col-6', 'form-check-label text-start'],
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
					'label' => [ 'col-form-label col-12', 'text-start'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => [ 'col-form-label col-md-3 col-12', 'text-start text-md-end'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => [ 'col-form-label col-lg-3 col-md-6 col-12', 'text-start text-md-end'],
					'wrapper' => ['col-lg-5 col-md-7 col-12'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group',
				]
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => [ 'col-form-label col-lg-3 col-md-4 col-6', 'text-start text-md-end'],
					'wrapper' => ['col-lg-3 col-md-4 col-6'],
					'error' => '',
					'hint' => '',
					'field' => 'form-group',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => '',
					'label' => [ 'col-form-label col-12', 'form-check-label text-start'],
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
					'label' => [ 'col-form-label col-12', 'text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => [ 'col-form-label col-md-3 col-12', 'text-start text-md-end text-break'],
					'wrapper' => ['col-md-9 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => [ 'col-form-label col-md-3 col-12', 'text-start text-md-end text-break'],
					'wrapper' => ['col-lg-5 col-md-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-3',
					'label' => [ 'col-form-label col-md-3 col-12', 'text-start text-md-end text-break'],
					'wrapper' => ['col-lg-5 col-md-6 col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => [ 'col-form-label col-lg-3 col-md-4 col-6', 'text-start text-md-end'],
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
					'label' => [ 'col-form-label col-12', 'text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'large' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => [ 'col-form-label col-12', 'text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'medium' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => [ 'col-form-label control-label'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				],
			],
			'short' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-3',
					'label' => [ 'col-form-label col-12', 'text-start text-break'],
					'wrapper' => ['col-12'],
					'error' => '',
					'hint' => '',
					'field' => '',
				]
			],
			'checkbox' => [
				'horizontalCssClasses' => [
					'offset' => 'offset-md-2 offset-sm-3',
					'label' => [ 'col-form-label col-lg-3 col-md-4 col-6', 'text-start text-md-end'],
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
				'label' => [ 'col-form-label col-12', 'text-start'],
				'wrapper' => ['col-12'],
				'error' => '',
				'hint' => '',
				'field' => '',
			]
		],
		'inline' => [
			'horizontalCssClasses' => [
				'offset' => 'offset-md-2 offset-3',
				'label' => [ 'col-form-label col-12', 'text-start'],
				'wrapper' => ['col-12'],
				'error' => '',
				'hint' => '',
				'field' => '',
			]
		]
	];

	#[Override]
	public function errorSummary($models, $options = []): string
	{
		if (!empty($options['dontShow'])) {
			return '';
		}
		$options['encode'] = $this->encodeErrorSummary;
		$showWarnings = ArrayHelper::remove($options, 'showWarnings');
		$ret = '';
		if ($showWarnings) {
			$warning_options = $options;
			Html::addCssClass($warning_options, $this->warningSummaryCssClass);
			$ret = self::warningSummary($models, $warning_options);
		}
		Html::addCssClass($options, $this->errorSummaryCssClass);
		$ret .= Html::errorSummary($models, $options);
		return $ret;
	}


	/**
	 * Based on Html::errorSummary
	 */
	protected function warningSummary($models, $options = [])
	{
		$header = ArrayHelper::remove($options, 'warningsHeader', '<p>' . Yii::t('yii', 'Warnings:') . '</p>');
		$footer = ArrayHelper::remove($options, 'footer', '');
		$encode = ArrayHelper::remove($options, 'encode', true);
		$showAllErrors = ArrayHelper::remove($options, 'showAllErrors', false);
		$emptyClass = ArrayHelper::remove($options, 'emptyClass', null);
		$layout = ArrayHelper::remove($options, 'layout', 'ul'); // New option for layout

		Html::addCssClass($options, $this->warningSummaryCssClass);
		$lines = self::collectWarnings($models, $encode, $showAllErrors);

		if (empty($lines)) {
			// still render the placeholder for client-side validation use
			$content = $this->getEmptyContent($layout);
			if ($emptyClass !== null) {
				$options['class'] = $emptyClass;
			} else {
				$options['style'] = isset($options['style']) ? rtrim($options['style'], ';') . '; display:none' : 'display:none';
			}
		} else {
			$content = $this->formatContent($lines, $layout);
		}

		return Html::tag('div', $header . $content . $footer, $options);
	}

	protected function getEmptyContent($layout)
	{
		switch ($layout) {
			case 'ol':
				return '<ol></ol>';
			case 'p':
				return '<p></p>';
			default:
				return '<ul></ul>';
		}
	}

	protected function formatContent($lines, $layout)
	{
		switch ($layout) {
			case 'ol':
				return '<ol><li>' . implode("</li>\n<li>", $lines) . '</li></ol>';
			case 'p':
				return '<p>' . implode("</p>\n<p>", $lines) . '</p>';
			default:
				return '<ul><li>' . implode("</li>\n<li>", $lines) . '</li></ul>';
		}
	}


	/**
	 * Return array of the validation errors
	 * @param Model|Model[] $models the model(s) whose validation errors are to be displayed.
	 * @param $encode boolean, if set to false then the error messages won't be encoded.
	 * @param $showAllErrors boolean, if set to true every error message for each attribute will be shown otherwise
	 * only the first error message for each attribute will be shown.
	 * @return array of the validation errors
	 * @since 2.0.14
	 *
	 * Based on Html::collectErrors
	 */
	static protected function collectWarnings($models, $encode, $showAllErrors)
	{
		$lines = [];
		if (!is_array($models)) {
			$models = [$models];
		}

		foreach ($models as $model) {
			$lines = array_unique(array_merge($lines, $model->getWarningSummary($showAllErrors)));
		}

		// If there are the same error messages for different attributes, array_unique will leave gaps
		// between sequential keys. Applying array_values to reorder array keys.
		$lines = array_values($lines);

		if ($encode) {
			foreach ($lines as &$line) {
				$line = Html::encode($line);
			}
		}

		return $lines;
	}


}
