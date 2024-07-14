<?php

/**
 * @copyright 2022, santilín
 * @license gpl
 */

namespace santilin\churros\helpers;

use Yii;
use yii\helpers\{ArrayHelper,Html,Url};
use yii\base\InvalidConfigException;

class FormHelper
{
	public const VIEWS_NVIEW_PARAM = '_v';
	static private $tabindex = 0;
	static public $operators = [
		'=' => '=',
		'<>' => '<>',
		'START' => 'Comienza por', 'NOT START' => 'No comienza por',
		'LIKE' => 'Contiene', 'NOT LIKE' => 'No contiene',
		'<=' => '<=',
		'>=' => '>=',
		'>' => '>',
		'<' => '<',
	];
	static public $extra_operators = [
		'SELECT' => 'Valor(es) de la lista',
		'BETWEEN' => 'entre dos valores',
		'NOT BETWEEN' => 'no entre dos valores',
	];

	static public function ti($inc=1)
	{
		static::$tabindex += $inc;
		return static::$tabindex;
	}

	static public function resetTabIndex($reset = 0)
	{
		static::$tabindex = $reset;
	}

	static public function extractFormName(array $views, int $_nv): string
	{
		if ($_nv > (count($views)-1) ) {
			$_nv = 0;
		}
		foreach($views as $kv => $view_info ) {
			if( $_nv-- == 0 ) {
				$form_class = $view_info[1];
				$form = new $form_class;
				return $form->formName();
			}
		}
		return '';
	}


	/// @return [ view_name, title, search_model, scopes, permissions ]
	static public function gridFromRequest(array $views, array $params): array
	{
		$_nv=$params[self::VIEWS_NVIEW_PARAM]??0;
		if( is_numeric($_nv) ) {
			if ($_nv > (count($views)-1) ) {
				$_nv = 0;
			}
			foreach($views as $kv => $view_info ) {
				if( $_nv-- == 0 ) {
					return $view_info;
				}
			}
		} else {
			if( isset($views[$_nv])	) {
				return $views[$_nv];
			}
		}
		$index = array_key_first($views);
		return $views[$index];
	}

	/// @return [ view_name, title, $model, $permissions ]
	static public function viewFromRequest(array $views, array $params): array
	{
 		$_nv=$params[self::VIEWS_NVIEW_PARAM]??0;
		if( is_numeric($_nv) ) {
			if ($_nv > (count($views)-1) ) {
				$_nv = 0;
			}
			foreach($views as $kv => $view ) {
				if( $_nv-- == 0 ) {
					return [ $kv, $view[0], $view[1], $view[2]??[], $view[3]??'' ];
				}
			}
		} else if (empty($_nv)) {
			return [ $_nv, $views[$_nv][0], $views[$_nv][1], $views[$_nv][2]??[], $views[$_nv][3]??''];
		} else {
			foreach ($views as $view => $view_info) {
				if ($view == $_nv || AppHelper::lastWord($view,'/') == $_nv) {
					return [ $view, $view_info[0], $view_info[1], $view_info[2]??[], $view_info[3]??''];
				}
			}
			throw new InvalidConfigException("$_nv: view not found");
		}
	}

	/// @return [ view_name, title, $model_name, $permissions ]
	static public function reportFromRequest(array $views, array $params): array
	{
		$_nv=$params[self::VIEWS_NVIEW_PARAM]??false;
		if ($_nv == false) {
			return ['reports',null,null,[]];
		}
		if (is_numeric($_nv)) {
			if ($_nv > (count($views)-1) ) {
				$_nv = 0;
			}
			foreach($views as $kv => $view ) {
				if( $_nv-- == 0 ) {
					return [ $kv, $view[0], $view[1], $view[2]??[], $view[3]??'' ];
				}
			}
		} else {
			if (!isset($views[AppHelper::lastWord($_nv,'/')])) {
				throw new InvalidConfigException("$_nv: view not found");
			}
			return [ $_nv, $views[$_nv][0], $views[$_nv][1], $views[$_nv][2]??[], $views[$_nv][3]??''];
		}
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
			$title = $button['title']??$name;
			$icon = $button['icon']??null;
			if( $icon ) {
				if (substr($icon, 0, 1) == '/') {
					$title = Html::img($icon, array_merge([
						'class'=>'icon',
						'aria' => [ 'hidden'=>'true', 'label' => $title ],
						'alt' => $title ], $button['iconOptions']??[]));
				} else {
					$hidable_title = "&nbsp;<span class=\"d-none d-lg-inline\">$title</span>";
					if( strpos($icon, '<i') !== FALSE ) {
						$title = $icon . $hidable_title;
					} else {
						$title = Html::tag('i', '', array_merge([
							'class' => 'icon',
							'aria' => [ 'hidden'=>'true', 'label' => $title ],
							'alt' => $title ], $button['iconOptions']??[])) . $hidable_title;
					}
				}
			}
			$url_return_to = ArrayHelper::remove($button, 'returnTo', null);
			switch( $button['type'] ) {
			case 'a':
				unset($button['htmlOptions']['name']);
				if( !isset($button['htmlOptions']['title']) ) {
					$button['htmlOptions']['title'] = $title;
				}
				Html::addCssClass($button['htmlOptions'], $name);
				if (empty($htmlOptions['role'])) {
					$button['htmlOptions']['role'] = 'button';
				}
				if (!empty($button['url'])) {
					$full_url = self::prepareButtonUrl($button['url'], $url_return_to);
					$ret[] = Html::a(
						$title,
						$full_url??'javascript:void(0);',
						$button['htmlOptions']);
				} else {
					$ret[] = Html::a($title, 'javascript:void(0);', $button['htmlOptions']);
				}
				break;
			case 'ajax':
				$request_url = Url::to($button['url']);
				$ajax_options = $button['ajaxOptions']??[];
// 				if (!YII_ENV_PROD) {
// 					$ajax_success = $ajax_options['success']??'console.log("Success: ", data)';
// 					$ajax_error = $ajax_options['error']??'console.log("Error: ", e.responseText)';
// 					$ajax_before_send = $ajax_options['beforeSend']??'console.log("Before send: ", jqXHR, settings)';
// 					$ajax_complete = $ajax_options['complete']??'console.log("Complete: ", jqXHR, textStatus)';
// 				} else {
					$ajax_success = $ajax_options['success']??'';
					$ajax_error = $ajax_options['error']??'';
					$ajax_before_send = $ajax_options['beforeSend']??'';
					$ajax_complete = $ajax_options['complete']??'';
// 				}
				$button['htmlOptions']['onclick'] = <<<ajax
javascript:
var _button = this;
$.ajax({
	url: '$request_url',
	type: 'get',
	dataType: 'json',
	complete: function (jqXHR, textStatus) {
$ajax_complete;
	},
	beforeSend: function (jqXHR, settings) {
$ajax_before_send;
	},
	success: function (data) {
$ajax_success;
	},
	error: function (e) {
$ajax_error;
	}
});
ajax;
				$ret[] = Html::button(
					$title,
					$button['htmlOptions']);
				break;
			case 'ajax-post':
				if (!is_array($button['url'])) {
					throw new InvalidConfigException("Ajax-post: button url must be array");
				}
				$request_url = Url::to(array_shift($button['url']));
				$data = json_encode($button['url']);
				$button['htmlOptions']['onclick'] = <<<ajax
javascript:
var btn = $(this);
$.ajax({
	url: '$request_url',
	type: 'post',
	dataType: 'json',
	data: $data,
	complete: function (jqXHR, textStatus) {
		console.log('Complete: ', jqXHR, textStatus);
	},
	beforeSend: function (jqXHR, settings) {
		console.log('Before send: ', jqXHR, settings);
	},
	success: function (data) {
		btn.hide();
		console.log('Success: ', data);
	},
	error: function (e) {
		console.log("Error", e.responseText);
	}
});
ajax;
				$ret[] = Html::button($title, $button['htmlOptions']);
				break;
			case 'submit':
				$ret[] = Html::submitButton($title, $button['htmlOptions']);
				break;
			case 'reset':
				$ret[] = Html::submitButton($title, $button['htmlOptions']);
				break;
			case 'button':
				if( isset($button['url']) && !isset($button['htmlOptions']['onclick']) ) {
					$full_url = self::prepareButtonUrl($button['url'], $url_return_to);
					$button['htmlOptions']['onclick'] = "window.location.href='$full_url'";
				}
				$ret[] = Html::button($title, $button['htmlOptions']);
				break;
			case 'select':
				if( isset($button['url']) && !isset($button['htmlOptions']['onchange']) ) {
					$full_url = self::prepareButtonUrl($button['url'], $url_return_to);
					$button['htmlOptions']['onchange'] = "window.location.href='$full_url'";
				}
				$ret[] = Html::dropDownList( $name, $button['selections']??null,
					$button['options'], $button['htmlOptions']);
				break;
			case "submitPostForm":
				$post_form = Html::beginForm(self::prepareButtonUrl($button['url'], $url_return_to), 'post', $button['formOptions']??[]);
				foreach ($button['hiddenInputs'] as $hidden_name => $hidden_value) {
					$post_form .= Html::hiddenInput($hidden_name, $hidden_value);
				}
				$post_form .= Html::submitButton($title, $button['htmlOptions']);
				$post_form .= Html::endForm();
				$ret[] = $post_form;
			}
		}
		return implode($sep, $ret);
	}

	static private function prepareButtonUrl(string|array $url, ?string $url_return_to): string
	{
		switch ($url_return_to) {
			case 'current':
				$url_return_to = Url::current();
				break;
			case 'home':
				$url_return_to = Url::home();
				break;
			case 'previous':
				$url_return_to = Url::previous();
				break;
			case 'referrer':
				$url_return_to = Yii::$app->request->referrer;
				break;
		}
		$full_url = Url::to($url);
		if ($url_return_to) {
			if (strpos($full_url, '?') !== FALSE) {
				$full_url .= '&';
			} else {
				$full_url .= '?';
			}
			$full_url .= "returnTo=$url_return_to";
		}
		return $full_url;
	}

	static public function hasPermission(bool|array|null $perms, string $perm): bool
	{
		if( is_bool($perms) ) {
			return $perms;
		}
		if( $perm == '' ) {
			return true;
		}
		if( $perms === [] || $perms === null) {
			return true;
		}
		return in_array($perm, $perms);
	}

	static public function removePermission(?array $perms, string $perm): array
	{
		$k = array_search($perm, $perms);
		if ($k !== false) {
			unset ($perms[$k]);
		}
		return $perms;
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

	static public function resolvePermissions(array|bool $all_disabled, array|bool $available, array|bool $granted = []): array|false
	{
		if ($available === false) {
			return false;
		}
		if ($all_disabled === false || $granted === false) {
			return [];
		}
		if ($granted == [] && $all_disabled == []) { // todos
			return $available;
		} else if ($granted == []) {
			return array_intersect($available, $all_disabled);
		} else {
			return array_intersect($available, $granted);
		}
	}

	static public function detailsTag(string $summary, string $body, bool $open = false,
						array $body_options = [], array $summary_options = [])
	{
		$dt = '<details';
		if ($open) {
			$dt .= ' open';
		}
		$encode_summary = ArrayHelper::remove($summary_options, 'encodeSummary', true);
		$summary_tag = ArrayHelper::remove($summary_options, 'summaryTag', null);
		$summary_tag_options = ArrayHelper::remove($summary_options, 'summarytagOptions', []);
		$dt .= Html::renderTagAttributes($body_options) . '>';
		$dt .= '<summary' . Html::renderTagAttributes($summary_options) . '>';
		$summary_content = $encode_summary ? Html::encode($summary) : $summary;
		if ($summary_tag) {
			$dt .= Html::tag($summary_tag, $summary_content, $summary_tag_options);
		} else {
			$dt .= $summary_content;
		}
		$dt .= "</summary>\n";
		$dt .= $body;
		$dt .= "</details>\n";
		return $dt;
	}

	static public function columnUrlCreatorWithReturnTo($action, $model, $key, $index, $column)
	{
		$params = is_array($key) ? $key : ['id' => (string) $key];
		$params[0] = $column->controller ? $column->controller . '/' . $action : $action;
		$params['returnTo'] = Url::current();
		return Url::toRoute($params);
	}

	static public function jsonColumnUrlCreatorWithReturnTo($action, $model, $key, $index, $column)
	{
		if ($column->controller) {
			$params = [ $column->controller . '/' . $key . '/' . $action];
		} else {
			$params = is_array($key) ? $key : ['id' => (string) $key];
		}
		$params['returnTo'] = Url::current();
		return Url::toRoute($params);
	}


	static public function toOpExpression($value, bool $strict, string $def_operator = null): array
	{
		if( is_string($value) && $value != '') {
			if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
				return json_decode($value, true);
			} else if (preg_match('/^(=|<>|<=|>=|>|<)(.*)$/', $value, $matches)) {
				return [ 'v' => $matches[2], 'op' => $matches[1] ];
			} else if (preg_match("/^IN\((.*)\)$/", $value, $matches)) {
				return [ 'v' => explode(',',$matches[1]), 'op' => '=' ];
			}
		}
		if (isset($value['op']) ) {
			if (isset($value['lft'])) {
				return [ 'op' => $value['op'], 'v' => $value['lft'] ];
			} else {
				return $value;
			}
		}
		if ($def_operator) {
			return [ 'op' => $def_operator, 'v' => $value ];
		} else {
			return [ 'op' => $strict ? '=' : 'LIKE', 'v' => $value ];
		}
	}


} // class
