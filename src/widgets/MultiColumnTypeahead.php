<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;
use yii\base\InvalidConfigException;
use kartik\typeahead\Typeahead;


class MultiColumnTypeahead extends Typeahead
{
	public $inputFields = [];
	public $remoteUrl;
	public $highlight = true;
	public $minLength = 3;
	public $displaySeparator = ', ';
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

		$set_fields_values = [];
		$concat_item = "item.{$this->inputFields[0]}";
		$idField = $this->inputFields[0];
		if ($this->concatToIdField) {
			$set_fields_values[] = <<<js
$(this).typeahead('val', selectedDatum[0].innerText);
js;
		} else {
			$set_fields_values[] = <<<js
$(this).typeahead('val', item.{$this->inputFields[0]});
js;
		}
		for ($i=1; $i<count($this->inputFields); $i++) {
			if ($concat_item != '') {
				$concat_item .= " + '{$this->displaySeparator}'";
			}
			$concat_item .= " + item.{$this->inputFields[$i]}";
			$fld_id = Html::getInputId($this->model, $this->inputFields[$i]);
			$set_fields_values[] = <<<js
	if (item.{$this->inputFields[$i]} != '') $('#$fld_id').val(item.{$this->inputFields[$i]});
js;
		}

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
				'suggestion' => new \yii\web\JsExpression(<<<jsexpr
function(item) { return '<div class="suggestion">' + $concat_item + '</div>'; }
jsexpr
				),
			],
			'display' => new \yii\web\JsExpression(<<<jsexpr
function(item) {
	return $concat_item;
}
jsexpr
			),
		]];


		$js_set_fields_values = implode("\n", $set_fields_values);
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
		$set_fields_values = [];
		if ($this->concatToIdField) {
			$set_fields_values[] = <<<js
			$(this).typeahead('val', selectedDatum[0].innerText);
js;
		} else {
			$set_fields_values[] = <<<js
			$(this).typeahead('val', datumParts[0]);
js;
		}
		for ($i=1; $i<count($this->inputFields); $i++) {
			$fld_id = Html::getInputId($this->model, $this->inputFields[$i]);
			$set_fields_values[] = <<<js
			if (datumParts[$i] !== undefined && datumParts[$i] != '') { $('#$fld_id').val(datumParts[$i]) };
js;
		}
		$js_set_fields_values = implode("\n", $set_fields_values);
		$js_id = str_replace('-','_',$id);
		$view->registerJS(<<<js
let mctahead_changed_$js_id = false;
$('#$id').change(function(e) {
	mctahead_changed_$js_id = true;
});
$('#$id').keydown(function(e) {
	if (e.keyCode === 13 && mctahead_changed_$js_id) {
		let selectedDatum = $(this).data('ttTypeahead').menu.getActiveSelectable();
		if (!selectedDatum) {
			selectedDatum = $(this).data('ttTypeahead').menu.getTopSelectable();
		}
		if (selectedDatum) {
			console.log(selectedDatum[0].innerText);
			const datumParts = selectedDatum[0].innerText.split('{$this->displaySeparator}');
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
