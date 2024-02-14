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
	public $formFields = [];
	public $suggestionsDisplay;
	public $display;
	public $remoteUrl;
	public $pageParam = 'page';
	public $fieldsParam = 'fields';
	public $perPageParam = 'pagesize';
	public $searchParam = 'search';
	public $createButton = false;
	public $limit = 5;

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
		// Create
		$ordered_form_fields = [];
		foreach ($this->formFields as $formField => $dbField) {
			if (is_array($dbField)) {
				$ordered_form_fields += $dbField;
			} else {
				$ordered_form_fields[$formField] = $dbField;
			}
		}
		$this->formFields = $ordered_form_fields;
		$set_dest_fields_values = [];
		$item_fields = [];
		foreach ($this->formFields as $formField => $dbField) {
			$fld_id = Html::getInputId($this->model, $formField);
 			$item_fields[] = "item.$dbField";
			$set_dest_fields_values[] = <<<js
	if (item.$dbField != '') $('#$fld_id').val(item.$dbField);
js;
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
		$this->dataset = [[
			'limit' => $this->limit,
			'remote' => [
				'url' => $this->remoteUrl . '?'
					. $this->searchParam . '=&' . $this->fieldsParam . '=&'
					. $this->pageParam . '=&' . $this->perPageParam . '=',
				'replace' => new \yii\web\JsExpression(<<<jsexpr
function(url, query) {
	const urlParams = new URLSearchParams(url);
	let fields = urlParams.get('{$this->fieldsParam}');
	let page = urlParams.get('{$this->pageParam}');
	let perpage = urlParams.get('{$this->perPageParam}');
	if (fields === undefined || fields == '' ) {
		fields = '$s_fields';
	}
	if (page === undefined || page == '' ) {
		page = '1';
	}
	if (perpage === undefined || perpage == '' ) {
		perpage = '{$this->limit}';
	}
	return url.split("?")[0] + "?{$this->searchParam}=" + query
		+ "&{$this->fieldsParam}=" + fields
		+ "&{$this->pageParam}=" + page + "&{$this->perPageParam}=" + perpage;
}
jsexpr
				),
			],
//  		'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('value')",
			'templates' => [
				'notFound' => '<div class="text-danger" style="padding:0 8px">' .
					Yii::t('churros', 'No results found') . '</div>',
				// The items in the dropdown
				'suggestion' => new \yii\web\JsExpression($this->suggestionsDisplay)
			],
 			'display' => new \yii\web\JsExpression($this->display),
		]];
		$js_set_fields_values = implode("\n", $set_dest_fields_values);
		$this->pluginEvents["typeahead:select"] = new \yii\web\JsExpression(<<<js
function(event, item) {
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
		$set_dest_fields_values = [];
		foreach ($this->formFields as $formField => $dbField) {
			$fld_id = Html::getInputId($this->model, $formField);
			$set_dest_fields_values[] = <<<js
			if (datumParts.$dbField !== undefined && datumParts.$dbField != '') { $('#$fld_id').val(datumParts.$dbField) };
js;
		}
		$js_set_fields_values = implode("\n", $set_dest_fields_values);
		$js_id = str_replace('-','_',$id);
		/// @todo make a module and refactor change an blur event handlers
		$view->registerJS(<<<js
let mctahead_changed_$js_id = false;
$('#$id').blur(function(e) {
	if (mctahead_changed_$js_id) {
		mctahead_changed_$js_id = false;
		let selectedDatum = $(this).data('ttTypeahead').menu.getActiveSelectable();
		if (!selectedDatum) {
			selectedDatum = $(this).data('ttTypeahead').menu.getTopSelectable();
		}
		if (selectedDatum) {
			const datumParts = $(selectedDatum[0]).data('ttSelectableObject');
			$js_set_fields_values
		}
	}
	return true;
});
$('#$id').keydown(function(e) {
	if (e.key.length === 1) {
		mctahead_changed_$js_id = true;
	}
	if (e.keyCode === 13 && mctahead_changed_$js_id) {
		mctahead_changed_$js_id = false;
		let selectedDatum = $(this).data('ttTypeahead').menu.getActiveSelectable();
		if (!selectedDatum) {
			selectedDatum = $(this).data('ttTypeahead').menu.getTopSelectable();
		}
		if (selectedDatum) {
			const datumParts = $(selectedDatum[0]).data('ttSelectableObject');
			$js_set_fields_values
			$('#$id').trigger("change");
			return true;
		}
	}
	return true;
});
js
	   );
	   parent::registerAssets();
    }

}
/*
?search=&page=
// 		'pluginEvents' => [
// 			"typeahead:active" => new \yii\web\JsExpression("function() {console.log('typeahead:active'); }"),
// 			  "typeahead:idle" => new \yii\web\JsExpression("function() {console.log('typeahead:idle'); }"),
// 			  "typeahead:open" => new \yii\web\JsExpression("function() {console.log('typeahead:open'); }"),
// 			  "typeahead:close" => new \yii\web\JsExpression("function() {console.log('typeahead:close'); }"),
// 			  "typeahead:change" => new \yii\web\JsExpression("function() {console.log('typeahead:change'); }"),
// 			  "typeahead:render" => new \yii\web\JsExpression("function() {console.log('typeahead:render'); }"),
// 			  "typeahead:select" => new \yii\web\JsExpression("function(event, item) { console.log(event); console.log(event.target.value); event.target.value = item.id; }"),
/*			  "typeahead:autocomplete" => new \yii\web\JsExpression("function() {console.log('typeahead:autocomplete'); }"),
			  "typeahead:cursorchange" => new \yii\web\JsExpression("function() {console.log('typeahead:cursorchange'); }"),
			  "typeahead:asyncrequest" => new \yii\web\JsExpression("function() {console.log('typeahead:asyncrequest'); }"),
			  "typeahead:asynccancel" => new \yii\web\JsExpression("function() {console.log('typeahead:asynccancel'); }"),
			  "typeahead:asyncreceive" => new \yii\web\JsExpression("function() {console.log('typeahead:asyncreceive'); }"),
// 		],





*/
