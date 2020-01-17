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
		$js = <<<JS
$('#_search_box_$id').change( function() {
	console.log("Changed");
	var el = $(this);
	console.log(el);
	var text = el.val();
	console.log(text);
	options = $('#$id option')
	options.each(function(i, obj) {
		console.log(obj);
		console.log(obj.text);
		if (obj.text.toUpperCase().indexOf(text) > -1) {
// 			obj.show();
		} else {
// 			obj.hide();
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
 		$ret = Html::input('text', null, null, [ 'id' => "_search_box_$id"] );
 		$ret .= Html::activeDropDownList($this->model, $this->attribute,
				$this->items, $this->options);
		return $ret;
    }
}
