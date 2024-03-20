<?php

namespace santilin\churros\widgets\grid;

use yii\grid\DataColumn;
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
	public $modal_title = "Title";


    /**
     * {@inheritdoc}
     * @todo Place the hellip instead of an space
     * @throws \yii\base\InvalidArgumentException
     */
    protected function renderDataCellContent($model, $key, $index)
    {
		$text = $this->getDataCellValue($model, $key, $index);
		if (!$text || !trim($text)) {
			return '';
		}
		if( $this->format == 'html' ) {
			$text = html_entity_decode(strip_tags($text));
		}
		if( $this->length == 0 ) {// || strlen($text)<=$this->length) {
			return $text;
		} else {
			/// @todo partir por el espacio más próximo
			$truncated_text = trim(mb_substr(trim($text), 0, $this->length));
			$encoded_text = Html::tag('p', Html::encode($text), $this->modalBodyOptions);
			$modal = <<<modal
<div class="modal fade" id="modalSeeMore_$key" tabindex="-1" aria-labelledby="modalSeeMore_$key" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h5 class="modal-title fs-5" id="modalSeeMoreTitle_$key">$this->modal_title</h5>
				<button type="button" class="btn-close" data-dismiss="modal" aria-label="Close">&times;</button>
			</div>
			<div class="modal-body bg-secondary" id="modalSeeMoreContenido_$key">
$encoded_text
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal" id="modalCopyClipBoardBtn_$key"><i class="fas fa-clipboard"></i></button>
			</div>
		</div>
	</div>
</div>
<script>
let generateQuoteBtn_$key = document.querySelector('#modalCopyClipBoardBtn_$key');
generateQuoteBtn_$key.addEventListener('click', () => {
	let contenedor = document.getElementById('modalSeeMoreContenido_$key');
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

			$text =  Html::tag('span', $truncated_text, $this->captionOptions) . Html::a('<i class="fa fa-external-link-alt"></i>', '#', [
				'title' => 'Pincha para leer más',
				'class' => "btn btn-outline-primary btn-sm",
				'data' => [
					'toggle' => 'modal',
					'target' => '#modalSeeMore_' . $key,
				],
                                'style' => 'position: absolute; right: 0;',
			]);
			return $modal . $text;
		}
    }

}
