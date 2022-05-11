<?php

/**
 * @copyright 2022, santilín
 * @license gpl
 */

namespace santilin\churros\helpers;

use Yii;

class FormHelper
{
	public const FIELD_CONFIG_1COL = [
		'horizontalCssClasses' => [
			'label' => 'col-sm-12 text-left',
			'offset' => 'col-sm-offset-1',
			'wrapper' => 'col-sm-12',
		]
	];

	public const FIELD_CONFIG_2COL = [
		'horizontalCssClasses' => [
			'label' => 'col-sm-3',
			'offset' => 'col-sm-offset-1',
			'wrapper' => 'col-sm-9',
		]
	];

	public const FIELD_CONFIG_3COL = [
		'horizontalCssClasses' => [
			'label' => 'col-sm-3',
			'offset' => 'col-sm-offset-1',
			'wrapper' => 'col-sm-9',
		]
	];

	public const FIELD_CONFIG_4COL = [
		'horizontalCssClasses' => [
			'label' => 'col-sm-3',
			'offset' => 'col-sm-offset-1',
			'wrapper' => 'col-sm-9',
		]
	];

	public const FIELD_CONFIG_HALF_SIZE = [
		'horizontalCssClasses' => [
			'wrapper' => 'col-sm-3',
		]
	];

	public const FIELD_CONFIG_ONE_THIRD_SIZE = [
		'horizontalCssClasses' => [
			'wrapper' => 'col-sm-2',
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
		if( $layout == "2cols" && !count($form_layout_rows) ) {
			$form_layout_rows = [];
			$row = [];
			foreach( array_keys($form_fields) as $key ) {
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
			if( count($row) != 0 ) {
				$form_layout_rows[] = $row;
			}
		}
		if( count($form_layout_rows) ) {
			foreach($form_layout_rows as $lrow ) {
				switch(count($lrow)) {
				case 1:
					echo '<div class="col-sm-12">';
					echo $form_fields[$lrow[0]];
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
				}
			}
		} else if ($layout == "horizontal" || $layout == "inline" ) {
			foreach( $form_fields as $name => $code ) {
				echo $form_fields[$name]. "\n";
			}
		}
	}

	/**
	 * fixes tabindex and layout of the form form_rows
	 */
	static public function fixLayoutFields(string $layout,
		array $form_layout_rows, array &$input_opts, array &$fldcfg)
	{
		if( count($form_layout_rows ) ) {
			foreach($form_layout_rows as $lrow) {
				foreach($lrow as $f) {
					$input_opts[$f]['tabindex'] = FormHelper::ti();
				}
				switch(count($lrow)) {
				case 1:
					$fldcfg[$lrow[0]] = FormHelper::FIELD_CONFIG_1COL;
					break;
				case 2:
					$fldcfg[$lrow[0]] =
					$fldcfg[$lrow[1]] = FormHelper::FIELD_CONFIG_2COL;
					break;
				case 3:
					$fldcfg[$lrow[0]] =
					$fldcfg[$lrow[1]] =
					$fldcfg[$lrow[2]] = FormHelper::FIELD_CONFIG_3COL;
					break;
				}
			}
		}
	}


} // class
