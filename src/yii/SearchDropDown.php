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
		$id = $this->options['id'];
		if( !($this->options['readonly']??false) ) {
			$js = <<<JS
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
	}

    public function run()
    {
        $this->registerClientScript();
        $id = $this->options['id'];
        $options_of_input = $this->options;
		$options_of_input['id'] = "_search_box_$id";
		if( $this->options['readonly']??false ) { // es readonly
			$ret = Html::activeHiddenInput($this->model, $this->attribute);
			$v = $this->model->{$this->attribute};
			$options_of_input['value'] = $this->items[$v];
			$options_of_input['name'] = 'sb_' . Html::getInputName($this->model, $this->attribute);
			unset($options_of_input['autofocus']);
			$options_of_input['tabindex'] = -1;
			$ret .= Html::activeInput('text', $this->model, $this->attribute, $options_of_input );
        } else {
			unset($options_of_input['prompt']);
			if( !isset($options_of_input['autocomplete']) ) {
				$options_of_input['autocomplete'] = "off";
			}
			$ret = '';
			if( count($this->items)>1 ) {
				$ret .= Html::input('text', null, null, $options_of_input );
			}
			// Avoid getting keyboard focus
			unset($this->options['autofocus']);
			$this->options['tabindex'] = '-1';
			$ret .= Html::activeDropDownList($this->model, $this->attribute,
					$this->items, $this->options);
		}
		return $ret;
    }
}
