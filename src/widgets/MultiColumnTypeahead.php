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
	public $inputFields = [];
	public $inputDisplayFields = [];
	public $remoteUrl;
	public $highlight = true;
	public $minLength = 3;
	public $displaySeparator = ',\u{2007}';
	public $createButton = false;
	public $pageParam = 'page';
	public $searchParam = 'search';
	public $concatToIdField = false;

    /**
     * Initializes the widget
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
	{
		if (count($this->inputFields)==0) {
            throw new InvalidConfigException("You must define al least one inputField");
		}
		if (count($this->inputDisplayFields) == 0) {
			$this->inputDisplayFields = $this->inputFields;
		}
		// Create
		$set_dest_fields_values = [];
		if ($this->concatToIdField) {
			$set_dest_fields_values[] = <<<js
$(this).typeahead('val', selectedDatum[0].querySelector(".suggestion-values").innerHTML);
js;
		} else {
			$set_dest_fields_values[] = <<<js
$(this).typeahead('val', item.{$this->inputFields[0]});
js;
		}
		for ($i=1; $i<count($this->inputDisplayFields); $i++) {
			$fld_id = Html::getInputId($this->model, $this->inputDisplayFields[$i]);
			$set_dest_fields_values[] = <<<js
	if (item.{$this->inputFields[$i]} != '') $('#$fld_id').val(item.{$this->inputFields[$i]});
js;
		}
		$s_item_props = "'" . implode("','", $this->inputDisplayFields) . "'";
		$this->dataset = [[
			'remote' => [
				'url' => $this->remoteUrl . '?' . $this->searchParam . '=&' . $this->pageParam . '=',
				'wildcard' => '%QUERY',
				'replace' => new \yii\web\JsExpression(<<<jsexpr
function(url, query) {
	var page = url.split("&{$this->pageParam}=")[1];
	if (page === undefined || page == '' ) {
		page = 1;
	}
	return url.split("?")[0] + "?{$this->searchParam}=" + query + "&{$this->pageParam}=" + page;
}
jsexpr
				),
			],
//  		'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('value')",
			'templates' => [
				'notFound' => '<div class="text-danger" style="padding:0 8px">No hay resultados.</div>',
				// The items in the dropdown
				'suggestion' => new \yii\web\JsExpression(<<<jsexpr
function(item) {
	const props=[$s_item_props];
	let s_items='';
	for (i=0; i<props.length; ++i) {
		if (item[props[i]]!='') {
			if (s_items.length != 0 ) {
				s_items += '{$this->displaySeparator}';
			}
			s_items += item[props[i]];
		}
	}
	return '<div data="' + JSON.stringify(item) + '" class="suggestion">' + s_items + '</div>'; }
jsexpr
				)
			],
			'display' => new \yii\web\JsExpression(<<<jsexpr
function(item) {
	return item.{$this->inputDisplayFields[0]};
}
jsexpr
			),
		]];
		$js_set_fields_values = implode("\n", $set_dest_fields_values);
		$this->pluginEvents["typeahead:select"] = new \yii\web\JsExpression(<<<js
function(event, item) {
	debugger;
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
		if ($this->concatToIdField) {
			$set_dest_fields_values[] = <<<js
		$(this).typeahead('val', selectedDatum[0].querySelector(".suggestion-values").innerHTML;
js;
		} else {
			$set_dest_fields_values[] = <<<js
		$(this).typeahead('val', datumParts.{$this->inputFields[0]});
js;
		}
		for ($i=1; $i<count($this->inputFields); $i++) {
			$fld_id = Html::getInputId($this->model, $this->inputDisplayFields[$i]);
			$set_dest_fields_values[] = <<<js
			if (datumParts.{$this->inputFields[$i]} !== undefined && datumParts.{$this->inputFields[$i]} != '') { $('#$fld_id').val(datumParts.{$this->inputFields[$i]}) };
js;
		}
		$js_set_fields_values = implode("\n", $set_dest_fields_values);
		$js_id = str_replace('-','_',$id);
		/// @todo make a module and refactor change an blur event handlers
		$view->registerJS(<<<js
let mctahead_changed_$js_id = false;
$('#$id').change(function(e) {
	mctahead_changed_$js_id = true;
});
$('#$id').blur(function(e) {
	if (mctahead_changed_$js_id) {
		let selectedDatum = $(this).data('ttTypeahead').menu.getActiveSelectable();
		if (!selectedDatum) {
			selectedDatum = $(this).data('ttTypeahead').menu.getTopSelectable();
		}
		if (selectedDatum) {
			const datumParts = $(selectedDatum[0]).data('ttSelectableObject');
			$js_set_fields_values
			return false;
		}
	}
});
$('#$id').keydown(function(e) {
	if (e.keyCode === 13 && mctahead_changed_$js_id) {
		let selectedDatum = $(this).data('ttTypeahead').menu.getActiveSelectable();
		if (!selectedDatum) {
			selectedDatum = $(this).data('ttTypeahead').menu.getTopSelectable();
		}
		if (selectedDatum) {
			const datumParts = $(selectedDatum[0]).data('ttSelectableObject');
			$js_set_fields_values
			return false;
		}
	}
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
