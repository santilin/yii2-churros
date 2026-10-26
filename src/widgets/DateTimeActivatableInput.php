<?php
namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;
use yii\web\View;

/**
 * A date that can be deactivated.
 *
 * Shows one of two things, depending on whether the field has a value or not:
 *  - with date: the date control from `QuickDateTimeInput`, to edit it, and a button
 *    that deactivates it
 *  - without date: the message the application sets in `inactiveMessage` and a button
 *    that reactivates it, leaving the control empty and ready for typing
 *
 * Both faces are always rendered and switched between with javascript, so
 * deactivating and reactivating does not reload the page. What gets saved is the
 * hidden field that `QuickDateTimeInput` already keeps: on deactivation it is
 * emptied, and with that the model receives null.
 *
 * Works for date, datetime and time: the type is deduced by the parent class from
 * the `format`.
 *
 *     $form->field($model, 'fin_ejecucion')->widget(DateTimeActivatableInput::class, [
 *         'format' => 'd/m/Y',
 *         'inactiveMessage' => 'No end date: the project is still open',
 *     ]);
 */
class DateTimeActivatableInput extends QuickDateTimeInput
{
	/** @var string the message shown when the date is deactivated */
	public $inactiveMessage;

	/** @var string the button that deactivates the date */
	public $deactivateLabel;

	/** @var string the button that reactivates it */
	public $activateLabel;

	/** @var array html options for the two buttons */
	public $buttonOptions = [ 'class' => 'btn btn-outline-secondary' ];

	/** @var array html options for the message */
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
	 * To the markup the parent class renders —the hidden one with the value and the
	 * masked control— the deactivate button, the message and the activate button are
	 * added. Both states end up in the html and one or the other is shown.
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

	/** Whether the model field is empty, i.e. the date is deactivated */
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
		// the hidden one that holds the real value; the visible masked one is `$id`
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
