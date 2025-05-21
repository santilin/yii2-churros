<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\{Html,Url};
use yii\base\InvalidConfigException;
use kartik\typeahead\Typeahead as KartikTypeahead;

class TypeaheadSelect extends KartikTypeahead
{
	public $relatedModel = null;
	public $idField = 'id';
	public $searchFields =  [];
	public $exactMatch = false;
	public $suggestionsDisplay;
	public $display;
	public $remoteUrl;
	public $pageParam = 'page';
	public $perPageParam = 'pagesize';
	public $searchParam = 'search';
	public $resultFormatParam = 'format';
	public $idFieldParam = 'id_field';
	public $searchFieldsParam = 'fields';
	public $createButton = false;
	public $limit = 5;

	private $hidden_id;
	private $typeahead_id;

	// from Yii InputWidget
    protected function getInput($type, $list = false)
	{
		$ret = Html::activeHiddenInput($this->model, $this->attribute, []);
		$ret .= parent::getInput($type);
		return $ret;
	}

	public function init()
	{
		if (empty($this->remoteUrl)) {
			throw new InvalidConfigException("remoteUrl can not be empty");
		}
		$this->hidden_id = $this->options['id']??Html::getInputId($this->model, $this->attribute);
		$this->typeahead_id = $this->options['id'] = $this->hidden_id . '_typeahead';
		$this->options['id'] = $this->hidden_id . '_typeadead';
		$this->options['name'] = '_typeadead_' . ($this->options['name']??Html::getInputName($this->model, $this->attribute));
		if (empty($this->suggestionsDisplay)) {
			$this->suggestionsDisplay = <<<js
function(item) {
    return '<div class="suggestion">' + item.text + '</div>';
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

		// Build the searchFields parameter as a JSON-encoded string
		$searchFieldsValue = !empty($this->searchFields)
			? urlencode(json_encode($this->searchFields))
			: '';

		// Compose the remote URL with fixed params
		$remote_url = Url::to($this->remoteUrl);
		if (!str_contains($remote_url, '?')) {
			$remote_url .= '?';
		} else {
			$remote_url .= '&';
		}
		$remote_url .= "{$this->searchParam}="; // will be filled by JS
		$remote_url .= "&{$this->idFieldParam}={$this->idField}";
		$remote_url .= "&{$this->resultFormatParam}=select";
		$remote_url .= "&{$this->searchFieldsParam}={$searchFieldsValue}";
		$remote_url .= "&{$this->pageParam}=1";
		$remote_url .= "&{$this->perPageParam}={$this->limit}";
		$this->dataset = [[
			'limit' => $this->limit,
			'remote' => [
				'url' => $remote_url,
				'replace' => new \yii\web\JsExpression(<<<jsexpr
function(url, query) {
    // Parse the URL and update only the search, page, and perPage params
    const u = new URL(url, window.location.origin);
    u.searchParams.set('{$this->searchParam}', query);

    // Optionally update page/perPage if you want to support pagination
    // (or remove these lines if you want to keep them fixed)
    // u.searchParams.set('{$this->pageParam}', '1');
    // u.searchParams.set('{$this->perPageParam}', '{$this->limit}');

    return u.toString();
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

		$this->pluginEvents["typeahead:select"] = new \yii\web\JsExpression(<<<js
function(event, item) {
	console.log(event,item);
	$("#{$this->typeahead_id}").val(item.text);
	$("#{$this->hidden_id}").val(item.id);
}
js
		);
		parent::init();
	}

    public function registerAssets()
    {
		$view = $this->getView();
		// Cuando se pulsa INTRO y está desplegado el menú de sugerencias, se selecciona la primera
		$set_dest_fields_values = $reset_dest_fields_values = [];
		$set_dest_fields_values[] = <<<js
$("#{$this->typeahead_id}").val(datumParts.text);
$("#{this->hidden_id}").val(datumParts.id);
js;
		$reset_dest_fields_values[] = <<<js
$("#{$this->typeahead_id}").val('');
$("#{$this->hidden_id}").val('');
js;
		$js_set_fields_values = implode("\n", $set_dest_fields_values);
		$js_reset_fields_values = implode("\n", $reset_dest_fields_values);
		$js_exact_match_field = "'$this->typeahead_id'";
		$js_id = str_replace('-','_',$this->typeahead_id);
		/// @todo make a module and refactor change an blur event handlers
		$view->registerJS(<<<js
let mctahead_exact_match_field_$js_id = $js_exact_match_field;
let mctahead_changed_$js_id = false;
$('#$this->typeahead_id').on('change', function(e) {
 	mctahead_changed_$js_id = true;
});
$('#$this->typeahead_id').on('focus', function(e) {
 	mctahead_changed_$js_id = false;
});
$('#$this->typeahead_id').on('blur', function(e) {
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
$('#{$this->typeahead_id}').on('keydown', function(e) {
 	if (e.key == "Delete" || e.key == "Backspace") {
		if (!mctahead_changed_$js_id) {
			$js_reset_fields_values
		}
 	} else if ((e.keyCode === 13 || e.keyCode == 8 ) && mctahead_changed_$js_id) {
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
