<?php
namespace santilin\churros\yii;
use yii\helpers\Html;
class HoursInput extends \yii\widgets\MaskedInput
{
	protected $_hidden_id;
	
	public function init()
	{
		parent::init();
		$this->_hidden_id = $this->options['id'];
		$this->options['id'] = $this->options['id'] . "_hours";
		$this->options['enableClientValidation'] = false;
	}
	
    /**
     * Registers the needed client script and options.
     */
    public function registerClientScript()
    {
		parent::registerClientScript();
        $view = $this->getView();
		$common_js = <<<JS
function toMinutes(hours)
{
	var a = hours.split(':'); 
	return (+a[0]) * 60 + (+a[1]);
}
function toHours(minutes)
{
	return String("00" + Math.floor(minutes / 60)).slice(-2) 
		+ ":" + String("00" + (minutes % 60)).slice(-2);
}	
JS;
		$view->registerJs($common_js, \yii\web\View::POS_READY, 'HoursInput');
		$id = $this->options['id'];
		$js = <<<JS
$('#$id').change( function() {
	console.log("Changed");
	var minutos = $(this).val().replace(/_/g, "0");
	$(this).val(minutos);
	console.log("Minutos: ",minutos, toMinutes(minutos));
	$('#{$this->_hidden_id}').val(toMinutes(minutos));
});
$('#$id').focus(function (e) {
	var that = this;
	setTimeout(function(){\$(that).select();},10);
	return false;
});
JS;
		$view->registerJs($js);
	}
    
    protected function renderInputHtml($type)
    {
		$this->value = \yii::$app->formatter->asHours(Html::getAttributeValue($this->model, $this->attribute));		
 		return Html::activeHiddenInput($this->model, $this->attribute)
			. Html::input($type, null, $this->value, $this->options);
    }
} 
