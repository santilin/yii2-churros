<?php
/*
 * https://github.com/RobinHerbots/Inputmask
 *
 * https://github.com/samdark/yii2-cookbook/blob/master/book/forms-activeform-js.md
 *
 */
namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;
use yii\base\InvalidArgumentException;
use yii\web\View;
use yii\widgets\MaskedInput;
use santilin\churros\helpers\DateTimeEx;
use santilin\churros\{ChurrosAsset,DateFormatterAsset};

class QuickDateTimeInput extends MaskedInput
{
	public $format;
	public $saveFormat;
	public $errorMessage;
	public $datetype;
	private $js_error_message;
	private $orig_id;

	public function init()
    {
		if (!$this->format) {
			throw new InvalidArgumentException("`format` must be set");
		}
		if (empty($this->mask)) {
			$this->mask = $this->formatToMask();
		}
        parent::init();
		if (!$this->datetype) {
			$this->datetype = DateTimeEx::guessTypeFromFormat($this->format);
		}
		if (!$this->saveFormat) {
			$dcm = \Yii::$app->getModule('datecontrol');
			switch ($this->datetype) {
				case 'date':
					if ($dcm) {
						$this->saveFormat = $dcm->getSaveFormat('date');
					}
					if (!$this->saveFormat) {
						$this->saveFormat = DateTimeEx::DATETIME_DATE_SQL_FORMAT;
					}
					if ($this->errorMessage == null) {
						$this->errorMessage = Yii::t('churros', "La fecha no es válida");
					}
					break;
				case 'datetime':
					if ($dcm) {
						$this->saveFormat = $dcm->getSaveFormat('datetime');
					}
					if (!$this->saveFormat) {
						$this->saveFormat = DateTimeEx::DATETIME_DATETIME_SQL_FORMAT;
					}
					if ($this->errorMessage == null) {
						$this->errorMessage = Yii::t('churros', "La fecha/hora no es válida");
					}
					break;
				case 'time':
					if ($dcm) {
						$this->saveFormat = $dcm->getSaveFormat('date');
					}
					if (!$this->saveFormat) {
						$this->saveFormat = DateTimeEx::DATETIME_TIME_SQL_FORMAT;
					}
					if ($this->errorMessage == null) {
						$this->errorMessage = Yii::t('churros', "La hora no es válida");
					}
					break;
			}
		}
        ChurrosAsset::register($this->view);
 		DateFormatterAsset::register($this->view);
		$this->js_error_message = addslashes($this->errorMessage);
        $this->clientOptions = array_merge([
			"insertMode" => false,
// 			'positionCaretOnClick' => 'select',
			'positionCaretOnTab' => 'select',
			'placeHolder' => $this->formatToPlaceHolder(),
		], $this->clientOptions);
        $this->orig_id = $this->options['id'];
        $this->options['id'] = $this->orig_id . "_date_disp";
	}


	protected function formatToMask()
	{
		$format = strtr($this->format, [
			" H:i:s" => "[ H:i:s]",
			" H:i" => "[ H:i]"
		]);
		return strtr($format, [
			'd' => '99',
			'm' => '99',
			'y' => '99[99]',
			'Y' => '99[99]',
			'H' => '99',
			'i' => '99',
			's' => '99',
		]);
	}

	protected function formatToPlaceHolder()
	{
		return strtr($this->format, [
			'd' => '__',
			'm' => '__',
			'y' => '__',
			'Y' => '____',
			'H' => '__',
			'i' => '__',
			's' => '__',
		]);
	}

	protected function formatToRegex()
	{
		return strtr($this->format, [
  			'/' => "/",
			'd' => '(?<day>0[1-9]|[12][0-9]|3[01])',
			'm' => '(?<month>0[1-9]|1[0-2])',
			'y' => '(?<year_short>[0-9][0-9])',
			'Y' => '(?<year_long>[0-9][0-9]([0-9]{0,2}))',
			'H' => '(?<hour>[01][0-9]|2[0-4])',
			'i' => '(?<minute>[0-5][0-9])',
			's' => '(?<second>[0-5][0-9])',
		]);
	}

    protected function renderInputHtml($type)
    {
		$hid_options = ['id' => $this->orig_id];
        if ($this->hasModel()) {
			if( empty($this->options['value']) ) {
				$value = Html::getAttributeValue($this->model, $this->attribute);
				if(!empty($value)) {
					$parsed_date = DateTimeEx::createFromFormat($this->saveFormat, $value);
					if( $parsed_date == null ) {
						switch ($this->datetype) {
							case 'date':
							case 'time':
								$parsed_date = DateTimeEx::createFromFormat(DateTimeEx::DATETIME_DATETIME_SQL_FORMAT, $value);
								break;
							case 'datetime':
								$parsed_date = DateTimeEx::createFromFormat(DateTimeEx::DATETIME_DATE_SQL_FORMAT, $value);
								break;
						}
					}
					if ($parsed_date != null) {
						$value = $parsed_date->format(self::parseFormat(\Yii::$app->formatter->dateFormat, $this->datetype));
					}
				}
				$this->options['value'] = $value;
			}
			$ret = Html::activeHiddenInput($this->model, $this->attribute, $hid_options);
			if (isset($this->options['name']) ) {
				$name = strtr($this->options['name'], '[]', '__');
			} else {
				$name = $this->attribute;
			}
			$this->options['name'] = ''; // "{$name}_date_disp";
            $ret .= Html::activeInput($type, $this->model, $this->attribute, $this->options);
        } else {
			throw new \Exception("to check");
// 			$ret = Html::hiddenInput( $this->name, $this->value, $hid_options);
// 			$name = str_replace(']','_', str_replace('[', '_', $this->name)) . "_date_disp";
// 			$ret .= Html::input($type, $name, $value, $this->options);
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
		$id = $this->options['id'];
		$orig_id = $this->orig_id;
		$format_as_regex = $this->formatToRegex();
		$js = <<<EOF
$('#$id').closest('form').submit(function(e) {
 	if (!window.yii.churros.dateInputChange($('#$id'), '$orig_id', '$this->format', '$this->saveFormat', '$format_as_regex', '$this->js_error_message')) {
 		e.preventDefault();
 		return false;
 	} else {
 		return true;
 	}
});
$('#$id').change(function(e) {
	if (!window.yii.churros.dateInputChange($(this), '$orig_id', '$this->format', '$this->saveFormat', '$format_as_regex', '$this->js_error_message')) {
		e.preventDefault();
		return false;
	} else {
		return true;
	}
});
$('#$id').blur(function(e) {
	if (!window.yii.churros.dateInputChange($(this), '$orig_id', '$this->format', '$this->saveFormat', '$format_as_regex', '$this->js_error_message')) {
		e.preventDefault();
		return false;
	} else {
		return true;
	}
});
EOF;
        $view->registerJs($js, View::POS_END, "QuickDateTimeInputJS_$id");
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
