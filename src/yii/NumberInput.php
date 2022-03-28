<?php
/*
 * https://github.com/RobinHerbots/Inputmask
 */
namespace santilin\churros\yii;

use yii\helpers\Html;
use yii\web\View;
use yii\widgets\MaskedInput;

class NumberInput extends MaskedInput
{
	private $orig_id;

	public function init()
    {
        parent::init();
        $this->orig_id = $this->options['id'];
        $this->options['id'] = $this->orig_id . "_number_disp";
	}

    protected function renderInputHtml($type)
    {
		$hid_options = [ 'id' => $this->orig_id ];
        if ($this->hasModel()) {
			if( empty($this->options['value']) ) {
				$value = Html::getAttributeValue($this->model, $this->attribute);
				if( !empty($value) ) {
					$value = number_format($value, 2);
				}
				$this->options['value'] = $value;
			}
			$ret = Html::activeHiddenInput($this->model, $this->attribute, $hid_options);
			if (isset($this->options['name']) ) {
				$name = str_replace(']','_', str_replace('[', '_', $this->options['name']));
			} else {
				$name = $this->attribute;
			}
			$name = $this->options['name'] = "{$name}_date_disp";
			$this->addChange($this->options, $this->orig_id);
            $ret .= Html::activeInput($type, $this->model, $this->attribute, $this->options);
        } else {
			throw new \Exception("to check");
		}
		return $ret;
    }

	/**
     * Registers the needed client script and options.
     */
    public function registerClientScript()
    {
		parent::registerClientScript();
		$view = $this->getView();
		$js = <<<EOF
// https://stackoverflow.com/a/499158
function dateInputSetSelectionRange(input, selectionStart, selectionEnd) {
  if (input.setSelectionRange) {
    input.setSelectionRange(selectionStart, selectionEnd);
  }
  else if (input.createTextRange) {
    var range = input.createTextRange();
    range.collapse(true);
    range.moveEnd('character', selectionEnd);
    range.moveStart('character', selectionStart);
    range.select();
  }
}

function numberInputChange(number_input, id)
{
	var number_js = number_input.value;
	console.log(number_js);
}
EOF;
        $view->registerJs($js, View::POS_HEAD, 'NumberInputWidgetJS');
    }

    private static function addChange(&$options, $id)
    {
		if( !isset($options['onchange']) ) {
			$options['onchange'] = "numberInputChange(this,'$id')";
		}
		if( !isset($options['onfocus']) ) {
			$options['onfocus'] = "dateInputSetSelectionRange(this,0,0)";
		}
    }

    private static function parseFormat($format, $type)
    {
        if (strncmp($format, 'php:', 4) === 0) {
            return substr($format, 4);
        } elseif ($format != '') {
            return FormatConverter::convertDateIcuToPhp($format, $type);
        } else {
            throw new InvalidConfigException("Error parsing '{$type}' format.");
        }
	}

}
