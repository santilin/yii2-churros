<?php
namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;
use santilin\churros\widgets\ReorderableListAsset;

class ReorderableListInput extends \yii\widgets\InputWidget
{
	public $items = [];
	public $itemOptions = [];
	public $deletable = false;

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
		$id = $this->options['id'] = $input_id . '_list';
		$this->view->registerJs(<<<js
$('#$id').sortable({
	stop: function(event, ui) {
		let frm = $('#$id').closest('form');
		frm.find('input[name="{$this->attribute}[]"]').each( function(k,i) { console.log(i);});
		$(this).find('li').each( function(k,li) {
			$('<input>').attr({
				type: 'hidden',
				name: '{$hid_name}[' + k + ']',
				value: li.dataset.id
			}).appendTo(frm);
			console.log("Added hidden input " + $(li).data('id'));
		});
	}
});
js
		);
		Html::addCssClass($this->options, 'sortable-list');
		$li_options = array_merge( [ 'data' => [ 'id' => null ]], $this->itemOptions);
		if ($this->deletable) {
			$this->view->registerJs("
    $(document).on('click', '.remove-button', function() {
        $(this).closest('li').remove();
    });
");
		}
		foreach ($this->items as $value => $item) {
			$li_options['data']['id'] = $value;
			if ($this->deletable) {
				$lis[] = Html::tag('li',
					Html::button(Yii::t('churros','<i class="bi bi-trash"></i>'), ['class' => 'btn btn-danger btn-xs remove-button'])
					. '&nbsp;&nbsp;<i class="bi bi-arrows-move"></i>&nbsp;' . $item, $li_options);
			} else {
				$lis[] = Html::tag('li', '<i class="bi bi-arrows-move"></i>&nbsp;' . $item, $li_options);
			}
		}
		return $ret . Html::tag('ul', implode('', $lis), $this->options);
	}

} // class
