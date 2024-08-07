<?php

namespace santilin\churros\widgets;

use yii\helpers\{Html,Url,StringHelper};
use yii\base\Component;
use yii\base\InvalidArgumentException;
use santilin\churros\helpers\AppHelper;

class ExpandableTextView extends Component
{
    /**
     * Maximun text length
     * @var
     */
	public $model = null;
	public $attribute = null;
    public $rows = 10;
    public $format = 'text';
	public $button_title = 'Leer más...';
	public $markdown_flavor = null;
	public $captionOptions = ['class' => 'see-more-content'];
	public $contentOptions = ['class' => 'see-more-container'];
	public $modalBodyOptions = [];
	public $modalTitle = null;

	public function init()
	{
		if (!$this->model || !$this->attribute) {
			throw new InvalidArgumentException("'model' and 'attribute' must be defined");
		}
		parent::init();
	}

    /**
     * {@inheritdoc}
     * @todo Place the hellip instead of an space
     */
    public function render()
    {
		if ($this->modalTitle === null) {
			$this->modalTitle = $this->model->getAttributeLabel($this->attribute);
		}
 		$text = $this->model->{$this->attribute};
		if ( $text === null || (is_string($text) && !trim($text)) || (is_array($text) && count($text) == 0) ) {
			return '';
		}
		if (is_array($text)) {
			$text = print_r($text, true);
		}
		$encoded_text = str_replace("\n", "<br/>", $text);
		if ($this->format == 'html') {
			$text = html_entity_decode(strip_tags($text));
		} else if ($this->format == 'markdown') {
			$text = \yii\helpers\Markdown::process($text, $this->markdown_flavor);
		}
		$text = trim($text);
		if( $this->rows == 0 ) {// || strlen($text)<=$this->length) {
			return $text;
		} else {
			$cell_key = strtr(get_class($this->model) . '.' . $this->model->getPrimaryKey() . '.' . $this->attribute, ['\\' => '_', '.' => '_']);
			if ($this->rows) {
				$truncated_text = AppHelper::getFirstLines($text, $this->rows);
			} else {
				$truncated_text = $text;
			}
			if ($truncated_text == $text) { // cabe perfectamente, no mostrar botón
				return $text;
			}
			$modal = <<<modal
<div class="modal fade" id="modalSeeMore_$cell_key" tabindex="-1" aria-labelledby="modalSeeMore_$cell_key" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
		<div class="modal-content">
			<div class="modal-header bg-secondary text-white">
				<button type="button" class="btn btn-primary p-1" data-bs-dismiss="modal" id="CopyClipboardBtn_$cell_key"><i class="bi bi-clipboard-plus"></i></button>
				<button type="button" class="btn btn-primary p-1" id="ShowSourceBtn_$cell_key"><i class="bi bi-file-binary"></i></button>
				&nbsp;
				<h1 class="modal-title fs-5" id="modalSeeMoreTitle_$cell_key">$this->modalTitle</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body" id="see_more_content_$cell_key">
$text
			</div>
		</div>
	</div>
</div>
<script>
/// todo: use class y data
let el_show_source_$cell_key = document.querySelector('#ShowSourceBtn_$cell_key');
	if (el_show_source_$cell_key) {
		el_show_source_$cell_key.addEventListener('click', () => {
		let contenedor = document.getElementById('see_more_content_$cell_key');
		if (contenedor) {
			contenedor.innerHTML = `$encoded_text`;
		}
	});
}
let el_copy_clipboard_$cell_key = document.querySelector('#CopyClipboardBtn_$cell_key');
if (el_copy_clipboard_$cell_key) {
	el_copy_clipboard_$cell_key.addEventListener('click', () => {
		let contenedor = document.getElementById('see_more_content_$cell_key');
		let text = contenedor.textContent;
		text = text.trim();
		var textArea = document.createElement("textarea");
		textArea.value = text;
		document.body.appendChild(textArea);
		textArea.select();
		document.execCommand('copy');
		document.body.removeChild(textArea);
	});
}
</script>
modal;

			$text =  Html::tag('span', $truncated_text, $this->captionOptions) . Html::a('<i class="bi bi-book"></i>', '#', [
				'title' => $this->button_title,
				'class' => "btn btn-outline-primary btn-sm py-0 px-1 me-1",
				'style' => 'position: absolute; right: 0; font-size:xx-small',
				'data' => [
					'bs-toggle' => 'modal',
					'bs-target' => "#modalSeeMore_$cell_key",
				],
			]);
			return $modal . $text;
		}
    }

}
