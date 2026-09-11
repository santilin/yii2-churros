<?php
namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;
use yii\web\View;

/**
 * Una fecha que se puede anular.
 *
 * Muestra una de dos cosas, según el campo tenga valor o no:
 *  - con fecha: el control de fecha de `QuickDateTimeInput`, para editarla, y un botón
 *    que la anula
 *  - sin fecha: el mensaje que ponga la aplicación en `inactiveMessage` y un botón que
 *    la vuelve a activar, dejando el control vacío y listo para escribir
 *
 * Las dos caras se pintan siempre y se cambia entre ellas con javascript, así que
 * anular y volver a activar no recarga la página. Lo que se guarda es el campo oculto
 * que ya mantiene `QuickDateTimeInput`: al anular se vacía, y con eso el modelo recibe
 * null.
 *
 * Sirve para date, datetime y time: el tipo lo deduce la clase padre del `format`.
 *
 *     $form->field($model, 'fin_ejecucion')->widget(DateTimeActivatableInput::class, [
 *         'format' => 'd/m/Y',
 *         'inactiveMessage' => 'Sin fecha de fin: el proyecto sigue abierto',
 *     ]);
 */
class DateTimeActivatableInput extends QuickDateTimeInput
{
	/** @var string el mensaje que se ve cuando la fecha está anulada */
	public $inactiveMessage;

	/** @var string el botón que anula la fecha */
	public $deactivateLabel;

	/** @var string el botón que la vuelve a activar */
	public $activateLabel;

	/** @var array opciones html de los dos botones */
	public $buttonOptions = [ 'class' => 'btn btn-outline-secondary' ];

	/** @var array opciones html del mensaje */
	public $messageOptions = [ 'class' => 'form-control-plaintext text-muted' ];

	public function init()
	{
		parent::init();
		if ($this->inactiveMessage === null) {
			$this->inactiveMessage = Yii::t('churros', 'Sin fecha');
		}
		if ($this->deactivateLabel === null) {
			$this->deactivateLabel = Yii::t('churros', 'Anular');
		}
		if ($this->activateLabel === null) {
			$this->activateLabel = Yii::t('churros', 'Poner fecha');
		}
	}

	/**
	 * Al hueco que pinta la clase padre —el oculto con el valor y el control con
	 * máscara— se le añaden el botón de anular, el mensaje y el botón de activar. Los
	 * dos estados salen en el html y se enseña uno u otro.
	 */
	protected function renderInputHtml($type)
	{
		$activo = !$this->isEmptyValue();
		$id = $this->options['id'];

		$input = Html::tag('div',
			parent::renderInputHtml($type)
				. Html::button($this->deactivateLabel,
					$this->buttonOptions + [ 'id' => "$id-deactivate", 'type' => 'button' ]),
			[ 'id' => "$id-active", 'class' => 'input-group',
			  'style' => $activo ? '' : 'display:none' ]);

		$mensaje = Html::tag('div',
			Html::tag('span', Html::encode($this->inactiveMessage), $this->messageOptions)
				. Html::button($this->activateLabel,
					$this->buttonOptions + [ 'id' => "$id-activate", 'type' => 'button' ]),
			[ 'id' => "$id-inactive", 'class' => 'input-group',
			  'style' => $activo ? 'display:none' : '' ]);

		return $input . $mensaje;
	}

	/** Si el campo del modelo está vacío, o sea, la fecha anulada */
	protected function isEmptyValue(): bool
	{
		if (!$this->hasModel()) {
			return empty($this->value);
		}
		return empty(Html::getAttributeValue($this->model, $this->attribute));
	}

	public function registerClientScript()
	{
		parent::registerClientScript();
		$id = $this->options['id'];
		// el oculto que guarda el valor de verdad; el visible con mascara es `$id`
		$hidden_id = $this->orig_id;
		$js = <<<JS
$('#$id-deactivate').on('click', function() {
	$('#$hidden_id').val('').trigger('change');
	$('#$id').val('');
	$('#$id-active').hide();
	$('#$id-inactive').show();
});
$('#$id-activate').on('click', function() {
	$('#$id-inactive').hide();
	$('#$id-active').show();
	$('#$id').val('').focus();
});
JS;
		$this->getView()->registerJs($js, View::POS_END, "DateTimeActivatableInputJS_$id");
	}
}
