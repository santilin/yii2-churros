<?php
/*
 * https://github.com/RobinHerbots/Inputmask
 */
namespace santilin\churros\widgets;

use yii\helpers\Html;
use yii\web\View;
use yii\widgets\MaskedInput;
use santilin\churros\helpers\DateTimeEx;

class DateInput extends MaskedInput
{
	private $orig_id;

	public function init()
    {
        parent::init();
        $this->orig_id = $this->options['id'];
        $this->options['id'] = $this->orig_id . "_date_disp";
        $this->clientOptions = array_merge([
			"insertMode" => false,
			'positionCaretOnClick' => 'select',
			'positionCaretOnTab' => 'select'
		], $this->clientOptions);

	}

    protected function renderInputHtml($type)
    {
		$hid_options = [ 'id' => $this->orig_id ];
        if ($this->hasModel()) {
			if( empty($this->options['value']) ) {
				$value = Html::getAttributeValue($this->model, $this->attribute);
				if( !empty($value) ) {
					$dcm = \Yii::$app->getModule('datecontrol');
					if( $dcm ) {
						$date = DateTimeEx::createFromFormat($dcm->getSaveFormat('date'), $value);
					}
					if( $date == null ) {
						$date = DateTimeEx::createFromFormat($dcm->getSaveFormat('datetime'), $value);
					}
					if( $date == null ) {
						$date = DateTimeEx::createFromFormat(DateTimeEx::DATETIME_DATE_SQL_FORMAT, $value);
					}
					if( $date == null ) {
						throw new \Exception("$value: Invalid date format for DateInput.");
					}
					$value = $date->format(self::parseFormat(\Yii::$app->formatter->dateFormat, 'date'));
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
// 			$ret = Html::hiddenInput( $this->name, $this->value, $hid_options);
// 			$name = str_replace(']','_', str_replace('[', '_', $this->name)) . "_date_disp";
// 			$this->addChange($this->options, $this->options['id']);
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

function dateInputParseSpanishDate(datestr)
{
	var datestr_parts = datestr.split('/');
	if( datestr_parts.length == 3 ) {
		var year = parseInt(datestr_parts[2]);
		if( isNaN(year) || year == 0 ) {
			year = new Date().getFullYear();
		} else if (year<100) {
			year += 2000;
		}
		var month = parseInt(datestr_parts[1]);
		if( isNaN(month) || month == 0 ) {
			month = new Date().getMonth() + 1;
		} else if (month > 12 ) {
			return false;
		}
		var day = parseInt(datestr_parts[0]);
		if( isNaN(day) ) {
			day = 0;
		} else if (day > 31) {
			return false;
		}
		var d = new Date(year, month-1, day);
		if( d.getFullYear() != year || d.getMonth() != month-1 || d.getDate() != day ) {
			return false;
		} else {
			return d;
		}
	} else {
		return false;
	}
}

function dateToSpanishFormat(date)
{
	var year = date.getFullYear();
	if( year < 1000 ) {
		year = "0" + year;
	}
	var month = date.getMonth() + 1;
	if (month < 10 ) {
		month = "0" + month;
	}
	var day = date.getDate();
	if( day < 10 ) {
		day = "0" + day;
	}
	return day + "-" + month + "-" + year;
}

function dateToSQLFormat(date)
{
	var year = date.getFullYear();
	var month = date.getMonth() + 1;
	if (month < 10 ) {
		month = "0" + month;
	}
	var day = date.getDate();
	if( day < 10 ) {
		day = "0" + day;
	}
	return year + "-" + month + "-" + day;
}

function dateInputChange(date_input, id)
{
	var date_js = dateInputParseSpanishDate(date_input.value);
	if (date_js == false ) {
		old_value = date_input.value;
		date_input.value = "00/00/0000";
		setTimeout(function() {
			date_input.value = old_value;
			date_input.focus();
		}, 1000);
	} else {
		console.log(date_js);
		date_input.value = dateToSpanishFormat( date_js );
		console.log(date_input.value);
		$('#' + id ).val( dateToSQLFormat( date_js ) );
		console.log($('#' + id).val());
	}
}
EOF;
        $view->registerJs($js, View::POS_HEAD, 'DateInputWidgetJS');
    }

    private static function addChange(&$options, $id)
    {
		if( !isset($options['onchange']) ) {
			$options['onchange'] = "dateInputChange(this,'$id')";
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
