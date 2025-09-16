<?php
namespace santilin\churros\widgets;
use yii\helpers\Html;

class DotDotInput extends \yii\widgets\InputWidget
{
	public $mask;
	public $dot = '.';

	public function run()
    {
		if (empty($this->mask)) {
			throw new InvalidConfigException("The DotDotInput mask must be set");
		}
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
		$js = <<<JS
$('#$id').focus( function() {
  var that = this;
  setTimeout(function(){ that.selectionStart = that.selectionEnd = 10000; }, 0);
});
JS;
		$view->registerJs($js);
	}

}
