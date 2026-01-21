<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\{Html,Url};
use yii\base\InvalidConfigException;
use kartik\typeahead\Typeahead as KartikTypeahead;
use yii\web\JsExpression;

class TypeaheadSelect extends KartikTypeahead
{
    public $relatedModel = null;
    public $idField = 'id';
    public $searchFields = [];
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
    public $limit = 8;
    public $format;

    private $hidden_id;
    private $typeahead_id;

    // from Yii InputWidget
    protected function getInput($type, $list = false)
    {
        // hidden real value
        $ret = Html::activeHiddenInput($this->model, $this->attribute, []);
        // visible text input from parent
        $ret .= parent::getInput($type);
        return $ret;
    }

    public function init()
    {
        if (empty($this->remoteUrl)) {
            throw new InvalidConfigException("remoteUrl can not be empty");
        }
        if (is_array($this->remoteUrl)) {
            $this->remoteUrl = Url::to($this->remoteUrl);
        }

        // base id for hidden field
        $this->hidden_id = $this->options['id'] ?? Html::getInputId($this->model, $this->attribute);

        // id for text field
        $this->typeahead_id = $this->hidden_id . '_typeahead';
        $this->options['id'] = $this->typeahead_id;
        $this->options['name'] = '_typeahead_' . ($this->options['name'] ?? Html::getInputName($this->model, $this->attribute));

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
        $remote_url .= "{$this->searchParam}="; // filled by JS
        $remote_url .= "&{$this->idFieldParam}={$this->idField}";
        $remote_url .= "&{$this->resultFormatParam}=select";
        $remote_url .= "&{$this->searchFieldsParam}={$searchFieldsValue}";
        $remote_url .= "&{$this->pageParam}=1";
        $remote_url .= "&{$this->perPageParam}={$this->limit}";

        $this->dataset = [[
            'limit' => $this->limit,
            'remote' => [
                'url' => $remote_url,
                'replace' => new JsExpression(<<<jsexpr
function(url, query) {
    const u = new URL(url, window.location.origin);
    u.searchParams.set('{$this->searchParam}', query);
    return u.toString();
}
jsexpr
                ),
            ],
            'templates' => [
                'notFound' => ($this->exactMatch
                    ? '<div class="text-danger" style="padding:0 8px">' . Yii::t('churros', 'No results found') . '</div>'
                    : ''),
                'suggestion' => new JsExpression($this->suggestionsDisplay),
            ],
            'display' => new JsExpression($this->display),
        ]];

        $this->pluginEvents["typeahead:select"] = new JsExpression(<<<js
function(event, item) {
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

        $set_dest_fields_values = [];
        $reset_dest_fields_values = [];

        $set_dest_fields_values[] = <<<js
$("#{$this->typeahead_id}").val(datumParts.text);
$("#{$this->hidden_id}").val(datumParts.id);
js;
        $reset_dest_fields_values[] = <<<js
$("#{$this->typeahead_id}").val('');
$("#{$this->hidden_id}").val('');
js;

        $js_set_fields_values = implode("\n", $set_dest_fields_values);
        $js_reset_fields_values = implode("\n", $reset_dest_fields_values);
        $js_exact_match_field = "'{$this->typeahead_id}'";
        $js_id = str_replace('-', '_', $this->typeahead_id);

        $view->registerJs(<<<js
let mctahead_exact_match_field_$js_id = $js_exact_match_field;
let mctahead_changed_$js_id = false;

$('#{$this->typeahead_id}').on('change', function(e) {
    mctahead_changed_$js_id = true;
});

$('#{$this->typeahead_id}').on('focus', function(e) {
    mctahead_changed_$js_id = false;
});

$('#{$this->typeahead_id}').on('blur', function(e) {
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
    } else if ((e.keyCode === 13 || e.keyCode == 8) && mctahead_changed_$js_id) {
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
