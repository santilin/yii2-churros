<?php

/**
 * @copyright 2022, santilín
 * @license gpl
 */

namespace santilin\churros\helpers;

use Yii;
use yii\helpers\{ArrayHelper,Html};

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
						'label' => 'col-sm-2',
						'wrapper' => 'col-sm-2',
					]
				],
				'2cols' => [
					'horizontalCssClasses' => [
						'label' => 'col-sm-2',
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

	static public function getGridFromRequest(array $views, array $params)
	{
		$_nv=$params[self::VIEWS_NVIEW_PARAM]??0;
		if( is_numeric($_nv) ) {
			if ($_nv > (count($views)-1) ) {
				$_nv = 0;
			}
			foreach($views as $kv => $view_info ) {
				if( $_nv-- == 0 ) {
					if( is_array($view_info) ) {
						// view_title, search_form, search_type, permissions
						return array_merge([$kv], $view_info);
					} else {
						return [$kv, $view_info];
					}
				}
			}
		} else {
			if( isset($views[$_nv])	) {
				return array_merge([$_nv], (array)$views[$_nv]);
			}
		}
		return reset($views);
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
					if( is_array($view) ) {
						return [$kv, $view['title']];
					} else {
						return [$kv, $view];
					}
				}
			}
		} else {
			if( isset($views[$_nv])	) {
				return [ $_nv, $views[$_nv]];
			}
		}
		return reset($views);
	}

	public static function setConfig(string $form_name, string $name, $value)
	{
		if (!isset($_SESSION['formconfig'])) {
			$_SESSION['formconfig'] = [];
		}
		if (!isset($_SESSION['formconfig'][$form_name])) {
			$_SESSION['formconfig'][$form_name] = [];
		}
		$_SESSION['formconfig'][$form_name][$name] = $value;
	}

	public static function getConfig(string $form_name, string $name, $default_value = null)
	{
		if( isset($_SESSION['formconfig'][$form_name][$name])
			&& $_SESSION['formconfig'][$form_name][$name] !== $default_value ) {
			return $_SESSION['formconfig'][$form_name][$name];
		} else if( isset(Yii::$app->params['formconfig'][$form_name][$name]) ) {
			return Yii::$app->params['formconfig'][$form_name][$name];
		} else {
			return $default_value;
		}
	}

	static public function displayButtons(array $buttons, string $sep = '&nbsp;'): string
	{
		$ret = [];
		foreach( $buttons as $name => $button ) {
			if( empty($button) ) {
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
			$title = $button['title'];
			$icon = $button['icon']??null;
			if( $icon ) {
				if( strpos($icon, '<i') !== FALSE ) {
					$title = "$icon $title";
				} else {
					$title = "<i class=\"$icon\" aria-hidden=\"true\"></i> $title";
				}
			}
			switch( $button['type'] ) {
			case 'a':
				unset($button['htmlOptions']['name']);
				Html::addCssClass($button['htmlOptions'], $name);
				$ret[] = Html::a(
					$title,
					$button['url']??'javascript:void(0);',
					$button['htmlOptions']);
				break;
			case 'submit':
				$ret[] = Html::submitButton(
					$title,
					$button['htmlOptions']);
				break;
			case 'button':
				$ret[] = Html::button(
					$title,
					$button['htmlOptions']);
				break;
			case 'select':
				$ret[] = Html::dropDownList( $name, $button['selections']??null,
					$button['options'], $button['htmlOptions']);
				break;
			}
		}
		return implode($sep, $ret);
// 		return '<ul><li>'. implode("</li><li>$sep", $ret) . '</li></ul>';
	}

	static public function hasPermission($perms, string $perm): bool
	{
		if( $perms === false ) {
			return false;
		}
		if( $perm == '' ) {
			return true;
		}
		if( $perms === []) {
			return true;
		}
		return in_array($perm, $perms);
	}


	static public function hasAllPermissions($perms, array $req_perms = []): bool
	{
		if( $perms === false ) {
			return false;
		}
		if( $req_perms === [] ) {
			return true;
		}
		if( $perms === []) {
			return true;
		}
		foreach( $req_perms as $req_perm ) {
			if( !in_array($req_perm, $perms) ) {
				return false;
			}
		}
		return true;
	}


	static public function hasAnyPermissions($perms, array $req_perms = []): bool
	{
		if( $perms === false ) {
			return false;
		}
		if( $req_perms === [] ) {
			return true;
		}
		if( $perms === []) {
			return true;
		}
		foreach( $req_perms as $req_perm ) {
			if( in_array($req_perm, $perms) ) {
				return true;
			}
		}
		return false;
	}


	static public function mergePermissions(array $final_perms, array $extra_perms): array
	{
		foreach( $extra_perms as $extra_perm ) {
			if( $extra_perm[0] == '-' ) {
				$extra_perm = substr($extra_perm,1);
				if( in_array($extra_perm, $final_perms) ) {
					ArrayHelper::removeValue($final_perms, $extra_perm);
				}
			} else {
				if( !in_array($extra_perm, $final_perms) ) {
					$final_perms[] = $extra_perm;
				}
			}
		}
		return $final_perms;
	}

} // class
