<?php
namespace santilin\churros\widgets;

use yii\helpers\Html;
class DecimalHoursInput extends \yii\widgets\InputWidget
{
	protected $_hidden_id;
	public $mask;
	public function init()
	{
		parent::init();
		$this->_hidden_id = $this->options['id'];
		$this->options['id'] = $this->options['id'] . "_hours";
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
		$view->registerJs($common_js, \yii\web\View::POS_END, 'DecimalHoursInput');
		$id = $this->options['id'];
		// https://stackoverflow.com/a/44209883
		$js = <<<JS
$('#{$id}').keydown( function(e) {
	if( e.keyCode == 190 ) {
		// change . into , (spanish hack)
        var s = $(this).val();
        var i = this.selectionStart;
        s = s.substr(0, i) + "," + s.substr(this.selectionEnd);
        $(this).val(s);
        this.selectionStart = this.selectionEnd = i + 1;
        return false;
	}
});
$('#$id').change( function() {
	var v = $(this).val().replace(',','.').replace('/[^0-9]//');
	var minutos = Math.round(parseFloat(v)*60);
	$('#{$this->_hidden_id}').val(minutos);
});
$('#{$id}').focus(function (e) {
	var that = $(this);
	if (that.val() == 0 || isNaN(that.val()) ) {
		that.val('');
	} else {
		setTimeout(function(){that.select();},50);
	}
	return true;
});
JS;
		$view->registerJs($js);
	}

    public function run()
    {
		$this->registerClientScript();
 		return Html::activeHiddenInput($this->model, $this->attribute)
			. Html::input('text', null, $this->value, $this->options);
    }
}
