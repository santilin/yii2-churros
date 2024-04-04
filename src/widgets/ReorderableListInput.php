<?php
namespace santilin\churros\widgets;

use yii\helpers\Html;
use santilin\churros\widgets\ReorderableListAsset;

class ReorderableListInput extends \yii\widgets\InputWidget
{
	public $items = [];
	public $itemOptions = [];

	public function init()
	{
		parent::init();
        if (!array_key_exists('id', $this->options)) {
            $this->options['id'] = Html::getInputId($this->model, $this->attribute);
        }
	}

    public function run()
    {
        ReorderableListAsset::register($this->view);
		$input_id = $this->options['id']??null;
		if ($input_id) {
			$hid_options = [ 'id' => $input_id ];
		} else {
			$hid_options = [];
		}
		$ret = Html::activeHiddenInput($this->model, $this->attribute . '[]', $hid_options);
		$lis = [];
		$id = $this->options['id'] = $input_id . '_list';
		$this->view->registerJs(<<<js
$('#$id').sortable({
	stop: function(event, ui) {
		let ordered_values = [];
		$(this).find('li').each( function(k,li) {
			ordered_values.push(li.dataset.id);
		});
		$('#$input_id').val(ordered_values);
	}
});
js
		);
		Html::addCssClass($this->options, 'sortable-list');
		$li_options = array_merge( [ 'data' => [ 'id' => null ]], $this->itemOptions);
		foreach ($this->items as $value => $item) {
			$li_options['data']['id'] = $value;
			$lis[] = Html::tag('li', '<i class="bi bi-arrows-move"></i>&nbsp;' . $item, $li_options);
		}
		return $ret . Html::tag('ul', implode('', $lis), $this->options);
	}

} // class
