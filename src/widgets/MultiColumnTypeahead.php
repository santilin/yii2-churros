<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;
use yii\base\InvalidConfigException;
use kartik\typeahead\Typeahead;

/**
 * This widget fills in the display fields when:
 * - The input blurs
 * - The enter key is pressed
 * - A suggestion is selected
 */

class MultiColumnTypeahead extends Typeahead
{
	public array $formFields = []; // $formField => $dbField
	public $suggestionsDisplay;
	public $display;
	public $remoteUrl;
	public array $scopes = [];
	public $pageParam = 'page';
	public $fieldsParam = 'fields';
	public $perPageParam = 'pagesize';
	public $searchParam = 'search';
	public $createButton = false;
	public $limit = 5;
	public $exactMatch = false;
	public $overwriteInputs = true;

	/**
	 * Initializes the widget
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	public function init()
	{
		if (count($this->formFields)==0) {
			throw new InvalidConfigException("You must define al least one formField");
		}
		$set_dest_fields_values = [];
		$item_fields = [];
		foreach ($this->formFields as $formField => $dbField) {
			$form_field_id = Html::getInputId($this->model, $formField);
			$item_fields[] = "item.$dbField";
			$js = '';
			if ($this->overwriteInputs) {
				$js = <<<js
$('#$form_field_id').val(item.$dbField);
js;
			} else {
				$js = <<<js
if (item.$dbField != '' && $('#$form_field_id').val() == '') $('#$form_field_id').val(item.$dbField);
js;
			}
			if (YII_ENV_DEV) {
				$js .= <<<js
console.log("$form_field_id = ", item.$dbField);
js;
			}
			$set_dest_fields_values[] = $js;
		}
		$s_item_fields = implode(',',$item_fields);
		if (empty($this->suggestionsDisplay)) {
			$this->suggestionsDisplay = <<<js
function(item) {
	const props = [$s_item_fields];
	let s_items='';
	for (i=0; i<props.length; ++i) {
		if (props[i]!='') {
			if (s_items.length != 0 ) {
				s_items += ', ';
			}
			s_items += props[i];
		}
	}
	return '<div data=\"' + JSON.stringify(item) + '\" class=suggestion>' + s_items + '</div>';
}
js;
		}
		$s_item_fields = $item_fields[0];
		if (empty($this->display)) {
			$this->display = <<<js
function(item) {
	const props = [$s_item_fields];
	let s_items='';
	for (i=0; i<props.length; ++i) {
		if (props[i]!='') {
			if (s_items.length != 0 ) {
				s_items += ', ';
			}
			s_items += props[i];
		}
	}
	return s_items;
}
js;
		}

		$s_fields = implode(",",$this->formFields);
		if (!str_contains($this->remoteUrl, '?')) {
			$remote_url = $this->remoteUrl . '?';
		} else {
			$remote_url = $this->remoteUrl . '&';
		}
		if (!empty($this->scopes)) {
			$remote_url .= 'scopes=' . implode(',', $this->scopes) . '&';
		}
		$this->dataset = [[
			'limit' => $this->limit,
			'remote' => [
				'url' => $remote_url
					. $this->searchParam . '=&' . $this->fieldsParam . '=&'
					. $this->pageParam . '=&' . $this->perPageParam . '=',
				'replace' => new \yii\web\JsExpression(<<<jsexpr
function(url, query) {
	const urlParams = new URLSearchParams(url);
	let fields = urlParams.get('{$this->fieldsParam}');
	let page = urlParams.get('{$this->pageParam}');
	let perpage = urlParams.get('{$this->perPageParam}');
	if (fields == '' ) {
		fields = '$s_fields';
	}
	if (page == '' ) {
		page = '1';
	}
	if (perpage == '' ) {
		perpage = '{$this->limit}';
	}
	return url.split("{$this->searchParam}=")[0] + "{$this->searchParam}=" + query
		+ "&{$this->fieldsParam}=" + fields
		+ "&{$this->pageParam}=" + page + "&{$this->perPageParam}=" + perpage;
}
jsexpr
				),
			],
			//  		'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('value')",
			'templates' => [
				'notFound' => ($this->exactMatch
				? '<div class="text-danger" style="padding:0 8px">' .Yii::t('churros', 'No results found') . '</div>'
				: '<div class="text-danger" style="padding:0 8px">' .Yii::t('churros', 'No suggestions found') . '</div>'
				),
				// The items in the dropdown
				'suggestion' => new \yii\web\JsExpression($this->suggestionsDisplay)
			],
			'display' => new \yii\web\JsExpression($this->display),
		]];
		$js_set_fields_values = implode("\n", $set_dest_fields_values);
		$this->pluginEvents["typeahead:select"] = new \yii\web\JsExpression(<<<js
function(event, item) {
console.log('typeahead:select');
	$js_set_fields_values
}
js
		);
		parent::init();
	}

	public function registerAssets()
	{
		$view = $this->getView();
		$id = $this->options['id'];

		// Cuando se pulsa INTRO y está desplegado el menú de sugerencias, se selecciona la primera
		$set_dest_fields_values = $reset_dest_fields_values = [];
		$nf = 0;
		foreach ($this->formFields as $formField => $dbField) {
			$form_field_id = Html::getInputId($this->model, $formField);
			$js = '';
			if ($this->overwriteInputs) {
				 $js = <<<js
if (datumParts.$formField !== undefined && datumParts.$formField != '') {
	$('#$form_field_id').val(datumParts.$formField) };
js;
			} else {
				$js = <<<js
if (datumParts.$formField !== undefined && datumParts.$formField != '' && $('#$form_field_id').val() == '') {
	$('#$form_field_id').val(datumParts.$formField) };
js;
			}
			if (YII_ENV_DEV) {
				$js .= <<<js
				console.log("$form_field_id = ", datumParts.$formField);
js;
			}
			$set_dest_fields_values[] = $js;
			if ($nf++>0) {
				$reset_dest_fields_values[] = <<<js
$('#$form_field_id').val('');
js;
			} else {
				$js_exact_match_field = "'$dbField'";
			}
		}
		$js_set_fields_values = implode("\n", $set_dest_fields_values);
		if ($this->overwriteInputs) {
			$js_reset_fields_values = implode("\n", $reset_dest_fields_values);
		} else {
			$js_reset_fields_values = '';
		}
		$js_id = str_replace('-','_',$id);
		/// @todo make a module and refactor change an blur event handlers
		$view->registerJS(<<<js
mctahead_exact_match_field_$js_id = $js_exact_match_field;
mctahead_changed_$js_id = false;
$('#$id').change(function(e) {
	mctahead_changed_$js_id = false;
});
$('#$id').focusin(function(e) {
	mctahead_changed_$js_id = false;
});
$('#$id').blur(function(e) {
	if (mctahead_changed_$js_id) {
		mctahead_changed_$js_id = false;
		let selectedDatum = $(this).data('ttTypeahead').menu.getActiveSelectable();
		if (!selectedDatum) {
			selectedDatum = $(this).data('ttTypeahead').menu.getTopSelectable();
		}
		if (selectedDatum) {
			const datumParts = $(selectedDatum[0]).data('ttSelectableObject');
			if (datumParts[mctahead_exact_match_field_$js_id] == $(this).val()) {
				$js_set_fields_values
			}
		}
	}
	return true;
});
$('#$id').keydown(function(e) {
	mctahead_changed_$js_id = true;
	if ((e.keyCode === 13 || e.keyCode == 8 || e.key == "Delete") && mctahead_changed_$js_id) {
		let selectedDatum = $(this).data('ttTypeahead').menu.getActiveSelectable();
		if (!selectedDatum) {
			selectedDatum = $(this).data('ttTypeahead').menu.getTopSelectable();
		}
		$js_reset_fields_values
		if (selectedDatum) {
			const datumParts = $(selectedDatum[0]).data('ttSelectableObject');
			if (datumParts[mctahead_exact_match_field_$js_id] == $(this).val()) {
				$js_set_fields_values
			}
		}
		mctahead_changed_$js_id = false;
	}
	return true;
});
js
		);
		parent::registerAssets();
	}

}

/*
	'pluginEvents' => [
		"typeahead:active" => new \yii\web\JsExpression("function() {console.log('typeahead:active'); }"),
		"typeahead:idle" => new \yii\web\JsExpression("function() {console.log('typeahead:idle'); }"),
		"typeahead:open" => new \yii\web\JsExpression("function() {console.log('typeahead:open'); }"),
		"typeahead:close" => new \yii\web\JsExpression("function() {console.log('typeahead:close'); }"),
		"typeahead:change" => new \yii\web\JsExpression("function() {console.log('typeahead:change'); }"),
		"typeahead:render" => new \yii\web\JsExpression("function() {console.log('typeahead:render'); }"),
		"typeahead:select" => new \yii\web\JsExpression("function(event, item) { console.log(event); console.log(event.target.value); event.target.value = item.id; }"),
		"typeahead:autocomplete" => new \yii\web\JsExpression("function() {console.log('typeahead:autocomplete'); }"),
		"typeahead:cursorchange" => new \yii\web\JsExpression("function() {console.log('typeahead:cursorchange'); }"),
		"typeahead:asyncrequest" => new \yii\web\JsExpression("function() {console.log('typeahead:asyncrequest'); }"),
		"typeahead:asynccancel" => new \yii\web\JsExpression("function() {console.log('typeahead:asynccancel'); }"),
		"typeahead:asyncreceive" => new \yii\web\JsExpression("function() {console.log('typeahead:asyncreceive'); }"),
	],
*/
