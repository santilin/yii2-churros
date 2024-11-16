<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\{Html,Url};
use yii\base\InvalidConfigException;
use kartik\typeahead\Typeahead as KartikTypeahead;

/**
 * This widget fills in the display fields when:
 * - The input blurs
 * - The enter key is pressed
 * - A suggestion is selected
 */

class Typeahead extends KartikTypeahead
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

	public function init()
	{
		if (empty($this->suggestionsDisplay)) {
			$this->suggestionsDisplay = <<<js
function(item) {
	return '<div class=suggestion>' + item.text + '</div>';
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
				'url' => Url::to($this->remoteUrl) . '?'
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

		parent::init();
	}

}
