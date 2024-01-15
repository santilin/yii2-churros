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
    public $length = 30;
    public $format = 'text';
	public $captionOptions = []; // @todo
	public $modalBodyOptions = [];
        public $titulo = "Título";

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
			$truncated_text = trim(mb_substr(trim($text), 0, $this->length));
			$encoded_text = Html::encode($text); // Html::tag('p', Html::encode($text), $this->modalBodyOptions);
			$modal = <<<modal
<div class="modal fade" id="modalSeeMore" tabindex="-1" aria-labelledby="modalSeeMore" style="display: none;" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h1 class="modal-title fs-5" id="modalLeerMasTitle">$this->titulo</h1>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="modalLeerMasContenido"></div>
				$encoded_text

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="modalLeerMasBtn">Copiar</button>
			</div>
		</div>
	</div>
</div>

modal;

			$text = Html::tag('span', $truncated_text, $this->captionOptions) . Html::a('<i class="bi bi-arrow-right-circle"></i>', '#', [
				'title' => 'Pincha para leer más',
				'class' => "btn btn-outline-primary btn-sm",
				'data' => [
					'bs-toggle' => 'modal',
					'bs-target' => '#modalSeeMore'
				],
				'style' => 'position: absolute; right: 0;',
			]);
			return $modal . $text;
		}
    }

}
