<?php
namespace santilin\churros\yii;
use yii\helpers\Html;

class DotDotInput extends \yii\widgets\InputWidget
{
	public $mask;

	public function init()
	{
		parent::init();
        if (!array_key_exists('id', $this->options)) {
            $this->options['id'] = Html::getInputId($this->model, $this->attribute);
        }
        if (!array_key_exists('name', $this->options)) {
            $this->options['name'] = Html::getInputName($this->model, $this->attribute);
        }
	}

	public function run()
    {
        $this->registerClientScript();
        return $this->renderInputHtml('text');
    }

    /**
     * Registers the needed client script and options.
     */
    public function registerClientScript()
    {
        $view = $this->getView();
		$id = $this->options['id'];
		$groups = join(',',$this->maskToGroups());
		$js = <<<JS
$('#$id').focus( function() {
  var that = this;
  setTimeout(function(){ that.selectionStart = that.selectionEnd = 10000; }, 0);
});
$('#$id').change( function(e) {
	const groups = [ $groups ];
	let re_str = '';
	for( i=0; i<groups.length; ++i ) {
		if( i!=0 ) {
			re_str += "\\\\.?";
		}
		re_str += "([0-9]{1," + groups[i] + "})";
		if( i>0 ) {
			re_str+='?'
		}
	}
    var rgx = new RegExp("^" + re_str + "$");
    console.log(rgx);
    if( !$(this).val().match(rgx) ) {
		alert("Los números y puntos no son correctos");
		e.preventDefault();
    }
    var parts = $(this).val().split('.');
    let ret = '';
    for( i=0; i<parts.length; ++i ) {
		if( i!=0 ) {
			ret += ".";
		}
		ret += parts[i].padStart(groups[i], '0')
    }
    $(this).val(ret);
});
JS;
		$view->registerJs($js);
	}

	protected function maskToGroups()
	{
		$parts = explode('.', $this->mask);
		$ret = [];
		foreach( $parts as $part) {
			$ret[] = strlen($part);
		}
		return $ret;
	}

}
