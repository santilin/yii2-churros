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
    private $remote_url;

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
        $this->remote_url = Url::to($this->remoteUrl);
        if (!str_contains($this->remote_url, '?')) {
            $this->remote_url .= '?';
        } else {
            $this->remote_url .= '&';
        }
        $this->remote_url .= "{$this->searchParam}="; // filled by JS
        $this->remote_url .= "&{$this->idFieldParam}={$this->idField}";
        $this->remote_url .= "&{$this->resultFormatParam}=select";
        $this->remote_url .= "&{$this->searchFieldsParam}={$searchFieldsValue}";
        $this->remote_url .= "&{$this->pageParam}=1";
        $this->remote_url .= "&{$this->perPageParam}={$this->limit}";

        $this->dataset = [[
            'limit' => $this->limit,
//             'remote' => [
//                 'url' => $this->remote_url,
//                 'replace' => new JsExpression(<<<jsexpr
// function(url, query) {
//     const u = new URL(url, window.location.origin);
//     u.searchParams.set('{$this->searchParam}', query);
//     return u.toString();
// }
// jsexpr
//                 ),
//             ],
            'remote' => [
                'url' => $this->remote_url,
                'wildcard' => '%QUERY', // enabling query replace
                'replace' => new JsExpression(<<<JS
function(url, query) {
    const u = new URL(url, window.location.origin);
    u.searchParams.set('{$this->searchParam}', query);
    u.searchParams.set('{$this->pageParam}', 1); // reset to page 1 at first
    return u.toString();
}
JS
                ),
                'ajax' => [
                    'type' => 'GET',
                    'cache' => false,
                ],
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

    $searchFieldsJson = !empty($this->searchFields)
        ? urlencode(json_encode($this->searchFields))
        : "''";


$view->registerJs(<<<JS
(function() {
    const input = $('#{$this->typeahead_id}');
    let currentPage = 1;
    let loading = false;

    function getMenuNode() {
        const tt = input.data('ttTypeahead');
        return tt && tt.menu && tt.menu.\$node ? tt.menu.\$node : null;
    }

    function createLoadMoreButton() {
        return $('<div class="tt-load-more text-center" style="padding:6px; cursor:pointer; color:#0d6efd;">')
            .text('Load more...')
            .on('click', async function() {
                if (loading) return;
                loading = true;
                const btn = $(this);
                btn.text('Loading...');
                currentPage++;

                const q = input.typeahead('val');
                const baseUrl = new URL("{$this->remoteUrl}", window.location.origin);
                baseUrl.searchParams.set('{$this->resultFormatParam}', 'select');
                baseUrl.searchParams.set('{$this->idFieldParam}', '{$this->idField}');
                if ($searchFieldsJson) {
                    baseUrl.searchParams.set('{$this->searchFieldsParam}', decodeURIComponent($searchFieldsJson));
                }

                // Now just modify the instance instead of cloning it
                baseUrl.searchParams.set('{$this->searchParam}', q);
                baseUrl.searchParams.set('{$this->pageParam}', currentPage);
                baseUrl.searchParams.set('{$this->perPageParam}', {$this->limit});

                try {
                    const response = await fetch(baseUrl);
                    const data = await response.json();

                    if (!data || data.length === 0) {
                        btn.text('No more results').css('color', '#6c757d').off('click');
                        loading = false;
                        return;
                    }

                    const menu = getMenuNode();
                    if (!menu) return; // Defensive check

                    btn.remove();
                    for (const item of data) {
                        const suggestion = $('<div class="tt-suggestion tt-selectable">')
                            .html(item.text)
                            .data('ttSelectableObject', item);
                        menu.append(suggestion);
                    }
                    if (data.length >= {$this->limit}) {
                        menu.append(createLoadMoreButton());
                    }
                    loading = false;
                } catch (e) {
                    debugger;
                    btn.text('Error – click to retry').css('color', 'red');
                    loading = false;
                }
            });
    }

    // Reset pagination on new async request
    input.on('typeahead:asyncrequest', function() {
        currentPage = 1;
    });

    // After results render, append "Load more..."
input.on('typeahead:render', function() {
    const menu = getMenuNode();
    if (!menu) return;

    // Only show "Load more" if the first page was full (limit items)
    if (menu.find('.tt-suggestion').length >= {$this->limit} && !menu.find('.tt-load-more').length) {
        menu.append(createLoadMoreButton());
    }
});

})();
JS
);

        parent::registerAssets();
    }
}
