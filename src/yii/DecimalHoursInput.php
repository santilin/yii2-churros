<?php
namespace santilin\churros\yii;
use yii\helpers\Html;
class DecimalHoursInput extends \yii\bootstrap\InputWidget
{
	protected $_hidden_id;
	public $mask;
	public function init()
	{
		parent::init();
		$this->_hidden_id = $this->options['id'];
		$this->options['id'] = $this->options['id'] . "_hours";
		$this->options['enableClientValidation'] = false;
		$this->options['step'] = 0.5;
	}

    /**
     * Registers the needed client script and options.
     */
    public function registerClientScript()
    {
        $view = $this->getView();
		$common_js = <<<JS
function timeToMinutes(time)
{
	var a = time.split(':');
	return (+a[0]) * 60 + (+a[1]);
}
function hoursToMinutes(hours)
{
	return Math.round(hours * 60);
}
function minutesToHours(minutes)
{
	return (Math.round( 100*minutes/60 ) / 100);
}
JS;
		$view->registerJs($common_js, \yii\web\View::POS_READY, 'DecimalHoursInput');
		$id = $this->options['id'];
		$js = <<<JS
$('#$id').change( function() {
	console.log("Changed");
	var minutos = Math.round(parseFloat($(this).val()) * 60);
	console.log("Minutos: ",minutos, hoursToMinutes(minutos));
	$('#{$this->_hidden_id}').val(hoursToMinutes(minutos));
});
$('#$id').focus(function (e) {
	var that = this;
	setTimeout(function(){\$(that).select();},10);
	return false;
});
JS;
		$view->registerJs($js);
	}

    public function run()
    {
		$this->registerClientScript();
 		return Html::activeHiddenInput($this->model, $this->attribute)
			. Html::input('input', null, $this->value, $this->options);
//  		return Html::activeInput("number", $this->model, $this->attribute)
// 			. Html::input('number', null, $this->value, $this->options);
    }
}
