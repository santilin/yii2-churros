<?php

namespace santilin\churros\widgets\grid;

use yii\helpers\Html;
use yii\helpers\Url;
/**
 * @todo botón copiar al portapapeles
 */
class ExpandableTextColumn extends DataColumn
{
    /**
     * Maximun text length
     * @var
     */
    public $length = 40;
    public $format = 'text';
	public $captionOptions = ['class' => 'see-more-content'];
	public $contentOptions = ['class' => 'see-more-container'];
	public $modalBodyOptions = [];
	public $modalTitle = null;

    /**
     * {@inheritdoc}
     * @todo Place the hellip instead of an space
     * @throws \yii\base\InvalidArgumentException
     */
    protected function renderDataCellContent($model, $key, $index)
    {
		if ($this->modalTitle === null) {
			$this->modalTitle = $this->getHeaderCellLabel();
		}
		$text = $this->getDataCellValue($model, $key, $index);
		if (is_array($text)) {
			$text = implode('<br/>', $text);
		}
		if (!$text || !trim($text)) {
			return '';
		}
		if( $this->format == 'html' ) {
			$text = html_entity_decode(strip_tags($text));
		}
		if( $this->length == 0 ) {// || strlen($text)<=$this->length) {
			return $text;
		} else {
			$cell_key = $index . $this->attribute;
			$truncated_text = trim(mb_substr(trim($text), 0, $this->length));
			$encoded_text = Html::tag('p', Html::encode($text), $this->modalBodyOptions);
			$modal = <<<modal
<div class="modal fade" id="modalSeeMore_$cell_key" tabindex="-1" aria-labelledby="modalSeeMore_$cell_key" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
		<div class="modal-content">
			<div class="modal-header bg-secondary text-white">
				<button type="button" class="btn btn-primary p-1" data-bs-dismiss="modal" id="modalCopyClipBoardBtn_$cell_key"><i class="bi bi-clipboard-plus"></i></button>
				&nbsp;
				<h1 class="modal-title fs-5" id="modalSeeMoreTitle_$cell_key">$this->modalTitle</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body" id="modalSeeMoreContent_$cell_key">
$encoded_text
			</div>
		</div>
	</div>
</div>
<script>
/// todo: use class y data
let generateQuoteBtn_$cell_key = document.querySelector('#modalCopyClipBoardBtn_$cell_key');
generateQuoteBtn_$cell_key.addEventListener('click', () => {
	let contenedor = document.getElementById('modalSeeMoreContent_$cell_key');
	let text = contenedor.textContent;
	text = text.trim();
	var textArea = document.createElement("textarea");
	textArea.value = text;
	document.body.appendChild(textArea);
	textArea.select();
	document.execCommand('copy');
	document.body.removeChild(textArea);
});
</script>
modal;

			$text =  Html::tag('span', $truncated_text, $this->captionOptions) . Html::a('<i class="bi bi-book"></i>', '#', [
				'title' => 'Pincha para leer más',
				'class' => "btn btn-outline-primary btn-sm py-0 px-1",
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
