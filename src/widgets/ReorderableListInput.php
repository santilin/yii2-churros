<?php
namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;
use santilin\churros\widgets\ReorderableListAsset;

class ReorderableListInput extends \yii\widgets\InputWidget
{
	public $items = [];
	public $itemOptions = [];
	public $removable = false;

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
		$hid_name = Html::getInputName($this->model, $this->attribute);
		$lis = [];
		$id = $this->options['id'] = str_replace('-','_',$input_id) . '_list';
		$this->view->registerJs(<<<js
function {$id}_update_order_input(element, frm) {
	frm.find('input[name^="$hid_name"]').remove();
	element.find('li').each( function(k,li) {
		$('<input>').attr({
			type: 'hidden',
			name: '{$hid_name}[' + k + ']',
			value: li.dataset.id
		}).appendTo(frm);
	});
}
let {$id}_sortable = $('#$id');
let {$id}_sortable_frm = {$id}_sortable.closest('form');
{$id}_sortable.sortable({
	stop: function(event, ui) {
		{$id}_update_order_input($(this), {$id}_sortable_frm);
	}
});
{$id}_update_order_input({$id}_sortable, {$id}_sortable_frm);~
js
		);
		Html::addCssClass($this->options, 'sortable-list');
		if ($this->removable) {
			$this->view->registerJs(<<<js
$('.remove-button').on('click', function() {
	let frm = $(this).closest('form');
	$(this).closest('li').remove();
	{$id}_update_order_input($('#$id'), frm);
});
js
			);
		}
		$li_options = array_merge( [ 'data' => [ 'id' => null ]], $this->itemOptions);
		foreach ($this->items as $value => $item) {
			$li_options['data']['id'] = $value;
			if ($this->removable) {
				$lis[] = Html::tag('li',
					Html::button(Yii::t('churros','<i class="fas fa-trash-alt"></i>'), ['class' => 'btn btn-danger btn-xs remove-button'])
					. '&nbsp;&nbsp;<i class="fas fa-arrows-alt"></i>&nbsp;' . $item, $li_options);
			} else {
				$lis[] = Html::tag('li', '<i class="fas fa-arrows-alt"></i>&nbsp;' . $item, $li_options);
			}
		}
		return $ret . Html::tag('ul', implode('', $lis), $this->options);
	}

} // class
