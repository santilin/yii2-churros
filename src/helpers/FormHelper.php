<?php

/**
 * @copyright 2022, santilín
 * @license gpl
 */

namespace santilin\churros\helpers;

use Yii;
use yii\helpers\Html;

class FormHelper
{
	public const BS_FIELD_CONFIGS = [
		'default' => [
		],
		'1col' => [
		],
		'2cols' => [
			'1col' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-1 one-column-row',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-11',
				]
			],
			'2cols' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-9',
				]
			],
			'3cols' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-9',
				]
			],
			'4cols' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-9',
				]
			],
			'1/3' => [
				'horizontalCssClasses' => [
					'wrapper' => 'col-sm-2',
				]
			],
			'1/2' => [
				'horizontalCssClasses' => [
					'wrapper' => 'col-sm-3',
				]
			]
		],
		'3cols' => [
			'1col' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-1 one-column-row',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-11',
				]
			],
			'2cols' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-9',
				]
			],
			'3cols' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-9',
				]
			],
			'4cols' => [
				'horizontalCssClasses' => [
					'label' => 'col-sm-3',
					'offset' => 'col-sm-offset-1',
					'wrapper' => 'col-sm-9',
				]
			],
			'1/3' => [
				'horizontalCssClasses' => [
					'wrapper' => 'col-sm-2',
				]
			],
			'1/2' => [
				'horizontalCssClasses' => [
					'wrapper' => 'col-sm-3',
				]
			]
		],
		'4cols' => [
		]
	];

	static private $tabindex = 0;

	static public function ti($inc=1)
	{
		static::$tabindex += $inc;
		return static::$tabindex;
	}

	static public function resetTabIndex($reset = 0)
	{
		static::$tabindex = $reset;
	}

	static public function layoutFields($layout, $form_fields, $form_layout_rows)
	{
		if ($layout == "horizontal" || $layout == "inline" ) {
			foreach( $form_fields as $name => $code ) {
				echo $form_fields[$name]. "\n";
			}
		} else if( count($form_layout_rows) ) {
			foreach($form_layout_rows as $lrow ) {
				switch(count($lrow)) {
				case 1:
					echo '<div class="row">';
					echo '<div class="col-sm-12">';
					echo $form_fields[$lrow[0]];
					echo "</div>";
					echo "</div>";
					break;
				case 2:
					echo '<div class="row">';
					echo '<div class="col-sm-6">';
					echo $form_fields[$lrow[0]];
					echo "</div>";
					echo '<div class="col-sm-6">';
					echo $form_fields[$lrow[1]];
					echo "</div>";
					echo "</div>";
					break;
				case 3:
					echo '<div class="row">';
					echo '<div class="col-sm-4">';
					echo $form_fields[$lrow[0]];
					echo "</div>";
					echo '<div class="col-sm-4">';
					echo $form_fields[$lrow[1]];
					echo "</div>";
					echo '<div class="col-sm-4">';
					echo $form_fields[$lrow[2]];
					echo "</div>";
					echo "</div>";
					break;
				case 4:
					echo '<div class="row">';
					echo '<div class="col-sm-3">';
					echo $form_fields[$lrow[0]];
					echo "</div>";
					echo '<div class="col-sm-3">';
					echo $form_fields[$lrow[1]];
					echo "</div>";
					echo '<div class="col-sm-3">';
					echo $form_fields[$lrow[2]];
					echo "</div>";
					echo '<div class="col-sm-3">';
					echo $form_fields[$lrow[3]];
					echo "</div>";
					echo "</div>";
					break;
				}
			}
		}
	}

	/**
	 * fixes tabindex and layout of the form form_rows
	 */
	static public function fixLayoutFields(string $layout,
		array &$form_layout_rows, array &$input_opts, array &$fldcfg)
	{
		if( !count($form_layout_rows ) ) {
			$row = [];
			switch( $layout ) {
			case "2cols":
				foreach( array_keys($input_opts) as $key ) {
					switch(count($row)) {
					case 2:
						$form_layout_rows[] = $row;
						$row = [];
					case 0:
						$row[0] = $key;
						break;
					case 1:
						$row[1] = $key;
						break;
					}
				}
				break;
			case "3cols":
				foreach( array_keys($input_opts) as $key ) {
					switch(count($row)) {
					case 3:
						$form_layout_rows[] = $row;
						$row = [];
					case 0:
						$row[0] = $key;
						break;
					case 1:
						$row[1] = $key;
						break;
					case 2:
						$row[2] = $key;
						break;
					}
				}
				break;
			}
			if( count($row) != 0 ) {
				$form_layout_rows[] = $row;
			}
		}
		foreach($form_layout_rows as $lrow) {
			foreach($lrow as $f) {
				if( isset($input_opts[$f]['tabindex']) ) {
					$input_opts[$f]['tabindex'] = FormHelper::ti();
				}
				switch(count($lrow)) {
				case 1:
					$fldcfg[$lrow[0]] = FormHelper::BS_FIELD_CONFIGS[$layout]['1col'];
					break;
				case 2:
					$fldcfg[$lrow[0]] =
					$fldcfg[$lrow[1]] = FormHelper::BS_FIELD_CONFIGS[$layout]['2cols'];
					break;
				case 3:
					$fldcfg[$lrow[0]] =
					$fldcfg[$lrow[1]] =
					$fldcfg[$lrow[2]] = FormHelper::BS_FIELD_CONFIGS[$layout]['3cols'];
					break;
				case 4:
					$fldcfg[$lrow[0]] =
					$fldcfg[$lrow[1]] =
					$fldcfg[$lrow[2]] = FormHelper::BS_FIELD_CONFIGS[$layout]['4cols'];
					break;
				}
			}
		}
	}

	static public function layoutInput(string $name, string $input, string $label,
		string $toolkit = 'bs3'): string
	{
		return Html::beginTag('div', ['class' => "form-group field-$name-cc"])
			. Html::label($label, '', ['class' => 'control-label'])
			. $input
			. Html::endTag('div');
	}


	public static function getConfig(string $name, string $form_name, $default_value = null)
	{
		if( isset($_SESSION['formconfig'][$form_name][$name]) ) {
			return $_SESSION['formconfig'][$form_name][$name];
		} else if( isset(Yii::$app->params['formconfig'][$form_name][$name]) ) {
			return Yii::$app->params['formconfig'][$form_name][$name];
		} else {
			return $default_value;
		}
	}

} // class
