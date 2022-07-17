<?php

/**
 * @copyright 2022, santilín
 * @license gpl
 */

namespace santilin\churros\helpers;

use Yii;
use yii\helpers\{Html};

class FormHelper
{
	public const VIEWS_NVIEW_PARAM = '_v';
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
		$ret = '';
		if ($layout == "horizontal" || $layout == "inline" ) {
			foreach( $form_fields as $name => $code ) {
				$ret .= $form_fields[$name]. "\n";
			}
		} else if( count($form_layout_rows) ) {
			foreach($form_layout_rows as $lrow ) {
				switch(count($lrow)) {
				case 1:
					$ret .= '<div class="row">';
					$ret .= '<div class="col-sm-12">';
					$ret .= $form_fields[$lrow[0]];
					$ret .= '</div>';
					$ret .= '</div>';
					break;
				case 2:
					$ret .= '<div class="row">';
					$ret .= '<div class="col-sm-6">';
					$ret .= $form_fields[$lrow[0]];
					$ret .= '</div>';
					$ret .= '<div class="col-sm-6">';
					$ret .= $form_fields[$lrow[1]];
					$ret .= '</div>';
					$ret .= '</div>';
					break;
				case 3:
					$ret .= '<div class="row">';
					$ret .= '<div class="col-sm-4">';
					$ret .= $form_fields[$lrow[0]];
					$ret .= '</div>';
					$ret .= '<div class="col-sm-4">';
					$ret .= $form_fields[$lrow[1]];
					$ret .= '</div>';
					$ret .= '<div class="col-sm-4">';
					$ret .= $form_fields[$lrow[2]];
					$ret .= '</div>';
					$ret .= '</div>';
					break;
				case 4:
					$ret .= '<div class="row">';
					$ret .= '<div class="col-sm-3">';
					$ret .= $form_fields[$lrow[0]];
					$ret .= '</div>';
					$ret .= '<div class="col-sm-3">';
					$ret .= $form_fields[$lrow[1]];
					$ret .= '</div>';
					$ret .= '<div class="col-sm-3">';
					$ret .= $form_fields[$lrow[2]];
					$ret .= '</div>';
					$ret .= '<div class="col-sm-3">';
					$ret .= $form_fields[$lrow[3]];
					$ret .= '</div>';
					$ret .= '</div>';
					break;
				}
			}
		}
		return $ret;
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

	public static function getViewFromRequest($views, $params)
	{
		if( ($_nv=intval($params[self::VIEWS_NVIEW_PARAM]??0)) > (count($views)-1) ) {
			$_nv = 0;
		}
		foreach($views as $kv => $view ) {
			if( $_nv-- == 0 ) {
				return $kv;
			}
		}
		return $views[0];
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

	static public function displayButtons($buttons)
	{
		$ret = [];
		foreach( $buttons as $name => $button ) {
			if( $button === null) {
				continue;
			}
			if (!empty($button['nocreate']) ) {
				continue;
			}
			if( !isset($button['htmlOptions']) ) {
				$button['htmlOptions'] = [];
			}
			if( !isset($button['htmlOptions']['name']) ) {
				$button['htmlOptions']['name'] = $name;
			}
			if( isset($button['htmlOptions']['autofocus']) ) {
				$button['htmlOptions']['tabindex'] = static::ti();
			}
			switch( $button['type'] ) {
			case 'a':
				$ret[] = Html::a(
					$button['title'],
					$button['url']??'javascript:void(0);',
					$button['htmlOptions']);
				break;
			case 'submit':
				$ret[] = Html::submitButton(
					$button['title'],
					$button['htmlOptions']);
				break;
			case 'button':
				$ret[] = Html::button(
					$button['title'],
					$button['htmlOptions']);
				break;
			case 'select':
				$ret[] = Html::dropDownList( $name, $button['selections']??null,
					$button['options'], $button['htmlOptions']);
				break;
			}
		}
		return implode('&nbsp;', $ret);
	}

} // class
