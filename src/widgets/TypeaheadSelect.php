<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;
use yii\base\InvalidConfigException;
use kartik\typeahead\Typeahead as KartikTypeahead;

/**
 * This widget fills in the display fields when:
 * - The input blurs
 * - The enter key is pressed
 * - A suggestion is selected
 */

class TypeaheadSelect extends KartikTypeahead
{
	public $exactMatch = false;
	public $suggestionsDisplay;
	public $display;
	public $remoteUrl;
	public $pageParam = 'page';
	public $perPageParam = 'pagesize';
	public $searchParam = 'search';
	public $resultFormatParam = 'format';
	public $createButton = false;
	public $limit = 5;

	// from Yii InputWidget
    protected function getInput($type, $list = false)
	{
		$save_options = $this->options;
		$this->options['id'] = Html::getInputId($this->model, $this->attribute) . '_typeahead';
 		$ret = Html::activeHiddenInput($this->model, $this->attribute, $this->options);
		$this->options = $save_options;
		$this->options['name'] = Html::getInputId($this->model, $this->attribute) . '_typeahead';
		$ret .= parent::getInput($type);
		$this->options = $save_options;
		return $ret;
	}

	public function init()
	{
		if (empty($this->suggestionsDisplay)) {
			$this->suggestionsDisplay = <<<js
function(item) {
debugger;
	return '<div data=\"' + item.id + '\" class=suggestion>' + item.text + '</div>';
}
js;
		}
 		if (empty($this->display)) {
			$this->display = <<<js
function(item) {
	return item.text;
}
js;
		}

		$this->dataset = [[
			'limit' => $this->limit,
			'remote' => [
				'url' => $this->remoteUrl . '?'
					. $this->searchParam . '=&' . $this->resultFormatParam . '='
					. $this->pageParam . '=&' . $this->perPageParam . '=',
				'replace' => new \yii\web\JsExpression(<<<jsexpr
function(url, query) {
	const urlParams = new URLSearchParams(url);
	let page = urlParams.get('{$this->pageParam}');
	let perpage = urlParams.get('{$this->perPageParam}');
	if (page == '' ) {
		page = '1';
	}
	if (perpage == '' ) {
		perpage = '{$this->limit}';
	}
	let resultFormat = urlParams.get('{$this->resultFormatParam}');
	if (resultFormat == '') {
		resultFormat = 'text';
	}
	return url.split("?")[0] + "?{$this->searchParam}=" + query
		+ "&{$this->resultFormatParam}=" + resultFormat
		+ "&{$this->pageParam}=" + page
		+ "&{$this->perPageParam}=" + perpage;
}
jsexpr
				),
			],
//  		'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('value')",
			'templates' => [
				'notFound' => ($this->exactMatch
					? '<div class="text-danger" style="padding:0 8px">' .Yii::t('churros', 'No results found') . '</div>'
					: ''),
				// The items in the dropdown
				'suggestion' => new \yii\web\JsExpression($this->suggestionsDisplay)
			],
 			'display' => new \yii\web\JsExpression($this->display),
		]];
		$hidden_name = $this->options['name']??Html::getInputName($this->model, $this->attribute);
 		$typeahead_id = ($this->options['id']??Html::getInputId($this->model, $this->attribute)) . '_typeahead';

		$set_dest_fields_values = $reset_dest_fields_values = [];
		$set_dest_fields_values[] = <<<js
$("#$typeahead_id").val(item.text);
$("input[name='$hidden_name']").val(item.id);
js;
		parent::init();
	}

    public function registerAssets2()
    {
		$view = $this->getView();
		$hidden_name = $this->options['name']??Html::getInputName($this->model, $this->attribute);
 		$typeahead_id = ($this->options['id']??Html::getInputId($this->model, $this->attribute)) . '_typeahead';

		// Cuando se pulsa INTRO y está desplegado el menú de sugerencias, se selecciona la primera
		$set_dest_fields_values = $reset_dest_fields_values = [];
		$set_dest_fields_values[] = <<<js
debugger;
$("#$typeahead_id").val(datumParts.text);
$("#input[name='$hidden_name']").val(datumParts.id);
js;
		$reset_dest_fields_values[] = <<<js
$("#$typeahead_id").val();
$("#input[name='$hidden_name']").val('');
js;
		$js_set_fields_values = implode("\n", $set_dest_fields_values);
		$js_reset_fields_values = implode("\n", $reset_dest_fields_values);
		$js_exact_match_field = "'$typeahead_id'";
		$js_id = str_replace('-','_',$typeahead_id);
		/// @todo make a module and refactor change an blur event handlers
		$view->registerJS(<<<js
let mctahead_exact_match_field_$js_id = $js_exact_match_field;
let mctahead_changed_$js_id = false;
$('#$typeahead_id').on('change', function(e) {
 	mctahead_changed_$js_id = true;
});
$('#$typeahead_id').on('focus', function(e) {
 	mctahead_changed_$js_id = false;
});
$('#$typeahead_id').on('blur', function(e) {
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
$('#$typeahead_id').on('keydown', function(e) {
 	if (e.key == "Delete" || e.key == "Backspace") {
		if (!mctahead_changed_$js_id) {
			$js_reset_fields_values
		}
 	} else if ((e.keyCode === 13 || e.keyCode == 8 ) && mctahead_changed_$js_id) {
		debugger;
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
