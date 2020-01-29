<?php
namespace santilin\churros\yii;

use yii\helpers\Html;

// https://stackoverflow.com/questions/18796221/creating-a-select-box-with-a-search-option/56590636#56590636

class SearchDropDown extends \yii\widgets\InputWidget
{
	public $items;

	public function init()
	{
		parent::init();
        if (!array_key_exists('id', $this->options)) {
            $this->options['id'] = Html::getInputId($this->model, $this->attribute);
        }
        if (!array_key_exists('name', $this->options)) {
            $this->options['name'] = Html::getInputName($this->model, $this->attribute);
        }
	}

    /**
     * Registers the needed client script and options.
     */
    public function registerClientScript()
    {
		$view = $this->getView();
		$name = $this->options['name'];
		$next_tab = isset($this->options['next_tab'])?$this->options['next_tab']:'';
		$id = $this->options['id'];
		$js = <<<JS
$('#_search_box_$id').keydown( function(e) {
	if( e.keyCode == 9 && e.shiftKey == false) {
		var dropdown = $('#$id');
		if( dropdown[0].selectedIndex > 0 ) {
			console.log("Avanzando");
			if( '$next_tab' != '' ) {
				$('#$next_tab').focus();
			} else {
				var next1 = $(":input:eq(" + ($(":input").index(dropdown) + 1) + ")");
				setTimeout( function() { console.log(next1); next1.focus(); }, 200 );
 			}
		}
	}
});

$('#_search_box_$id').keyup( function(e) {
	if( e.keyCode == 9 && e.shiftKey == false) {
		return; // The tab key pressed that got the focus
	}
	var el = $(this);
	var text = el.val().toUpperCase();
	options = $('#$id option');
	var found = false;
	options.each(function(i, obj) {
		if (obj.text.toUpperCase().indexOf(text) == 0) {
			obj.selected = true;
			found = true;
			return false;
		}
	});
	if (found) {
		return;
	}
	options.each(function(i, obj) {
		if (obj.text.toUpperCase().indexOf(text) > -1) {
			obj.selected = true;
			return false;
		}
	});
});
JS;
		$view->registerJs($js);
	}

    public function run()
    {
        $this->registerClientScript();
        $id = $this->options['id'];
        $options_of_input = $this->options;
        $options_of_input['id'] = "_search_box_$id";
        if( !isset($options_of_input['autocomplete']) ) {
			$options_of_input['autocomplete'] = "off";
		}
        $ret = '';
        if( count($this->items)>1 ) {
			$ret .= Html::input('text', null, null, $options_of_input );
		}
 		$ret .= Html::activeDropDownList($this->model, $this->attribute,
				$this->items, $this->options);
		return $ret;
    }
}
