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
	public const WIDGET_CONFIGS = [
		'bs3' => [
			'default' => [
			],
			'1col' => [
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
			],
			'4cols' => [
				'1col' => [
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
				'4cols' => [
					'horizontalCssClasses' => [
						'label' => 'col-sm-3',
						'wrapper' => '',
					],
					'options' => [ 'class' => 'control-group col-sm-2' ],
				],
			],
		],
		'bs4' => [
			'1col' => [
				'1col' => [
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
						'label' => 'col-sm-2 col-form-label',
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
			],
			'4cols' => [
				'1col' => [
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
			'short' => [
				'1col' => [
					'horizontalCssClasses' => [
						'label' => 'col-sm-1',
						'wrapper' => 'col-sm-1',
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
						'offset' => 'offset-sm-1',
						'wrapper' => 'col-sm-3',
					]
				],
				'4cols' => [
					'horizontalCssClasses' => [
						'label' => 'col-sm-3',
						'wrapper' => '',
					],
				],
			],
		],
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
		if ($layout == "horizontal" )
			die("Horizontal layout");
		if ( $layout == '1col' || $layout == "inline" ) {
			foreach( $form_fields as $name => $code ) {
				$ret .= $form_fields[$name]. "\n";
			}
		} else if( count($form_layout_rows) ) {
			// Check if some fields have been removed after setting the layout
			foreach($form_layout_rows as $lrowkey => $lrow ) {
				foreach( $lrow as $ffkey => $ff ) {
					if( $form_fields[$ff] === false ) {
						unset( $form_layout_rows[$lrowkey][$ffkey] );
					}
				}
			}
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
					$ret .= $form_fields[$lrow[0]];
					$ret .= $form_fields[$lrow[1]];
					$ret .= $form_fields[$lrow[2]];
					$ret .= $form_fields[$lrow[3]];
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
		array &$form_layout_rows, array &$input_opts, array &$fldcfg, string $widgets_ver = 'bs4')
	{
		if( !count($form_layout_rows ) ) {
			$row = [];
			switch( $layout ) {
			case 'horizontal':
			case '1col':
				foreach( array_keys($input_opts) as $key ) {
					$form_layout_rows[] = [ $key ];
				}
				break;
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
			case "4cols":
				foreach( array_keys($input_opts) as $key ) {
					switch(count($row)) {
					case 4:
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
					case 3:
						$row[3] = $key;
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
				if( intval($input_opts[$f]['tabindex']??-1) !== -1 ) {
					$input_opts[$f]['tabindex'] = FormHelper::ti();
				}
			}
			switch(count($lrow)) {
			case 1:
				FormHelper::setFldCfg($fldcfg, $lrow[0], $widgets_ver, $layout, '1col');
				break;
			case 2:
				FormHelper::setFldCfg($fldcfg, $lrow[0], $widgets_ver, $layout, '2cols');
				FormHelper::setFldCfg($fldcfg, $lrow[1], $widgets_ver, $layout, '2cols');
				break;
			case 3:
				FormHelper::setFldCfg($fldcfg, $lrow[0], $widgets_ver, $layout, '3cols');
				FormHelper::setFldCfg($fldcfg, $lrow[1], $widgets_ver, $layout, '3cols');
				FormHelper::setFldCfg($fldcfg, $lrow[2], $widgets_ver, $layout, '3cols');
				break;
			case 4:
				FormHelper::setFldCfg($fldcfg, $lrow[0], $widgets_ver, $layout, '4cols');
				FormHelper::setFldCfg($fldcfg, $lrow[1], $widgets_ver, $layout, '4cols');
				FormHelper::setFldCfg($fldcfg, $lrow[2], $widgets_ver, $layout, '4cols');
				FormHelper::setFldCfg($fldcfg, $lrow[3], $widgets_ver, $layout, '4cols');
				break;
			}
		}
		foreach($fldcfg as $k => $v) {
			unset($fldcfg[$k]['layout']);
		}

	}

	static public function setFldCfg(&$fldcfg, $field, $widgets_ver, $layout, $cols)
	{
		if( $fldcfg[$field]['layout']??null !== null ) {
			$l = $fldcfg[$field]['layout'];
			$fldcfg[$field] = FormHelper::WIDGET_CONFIGS[$widgets_ver][$l][$cols];
		} else {
			$fldcfg[$field] = FormHelper::WIDGET_CONFIGS[$widgets_ver][$layout][$cols];
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

	static public function getViewFromRequest($views, $params)
	{
		$_nv=$params[self::VIEWS_NVIEW_PARAM]??0;
		if( is_numeric($_nv) ) {
			if ($_nv > (count($views)-1) ) {
				$_nv = 0;
			}
			foreach($views as $kv => $view ) {
				if( $_nv-- == 0 ) {
					return $kv;
				}
			}
		} else {
			if( isset($views[$_nv])	) {
				return $_nv;
			}
		}
		return array_keys($views)[0];
	}

	static public function getViewTitleFromRequest($views, $params)
	{
		$_nv=$params[self::VIEWS_NVIEW_PARAM]??0;
		if( is_numeric($_nv) ) {
			if ($_nv > (count($views)-1) ) {
				$_nv = 0;
			}
			foreach($views as $kv => $view ) {
				if( $_nv-- == 0 ) {
					return $view;
				}
			}
		} else {
			if( isset($views[$_nv])	) {
				return $views[$_nv];
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

	static public function layoutButtons($buttons, string $fields_layout, string $widgets_ver = 'bs4')
	{
		switch ($widgets_ver) {
		case 'bs4':
			switch($fields_layout) {
			case '2col':
				$classes = 'offset-md-2 col-sm-10';
				$ret = <<<html
<div class="row"><div class="col-sm-12">
	<div class="form-group buttons"><div class="$classes">
html;
				$ret .= static::displayButtons($buttons);
				$ret .= <<<html
	</div></div><!--buttons form-group-->
</div></div>
html;
				break;
			case '1col':
			default:
				$classes = 'offset-md-2 col-sm-10';
				$ret = <<<html
<div class="row"><div class="col-sm-12">
	<div class="form-group buttons"><div class="$classes">
html;
				$ret .= static::displayButtons($buttons);
				$ret .= <<<html
	</div></div><!--buttons form-group-->
</div></div>
html;
				break;
			}
			break;
		case 'bs3':
			switch( $fields_layout) {
			case '1col':
				$classes = 'col-md-offset-3 col-sm-9';
				$ret = <<<html
<div class="row"><div class="col-sm-12">
	<div class="form-group buttons"><div class="$classes">
html;
				$ret .= static::displayButtons($buttons);
				$ret .= <<<html
	</div></div><!--buttons form-group-->
</div></div>
html;
				break;
			case '2cols':
			default:
				$classes = 'col-md-offset-3 col-sm-9';
				$ret = <<<html
<div class="row"><div class="col-sm-6">
	<div class="form-group buttons"><div class="$classes">
html;
				$ret .= static::displayButtons($buttons);
				$ret .= <<<html
	</div></div><!--buttons form-group-->
</div></div>
html;
				break;
			}
			break;
		}
		return $ret;
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
