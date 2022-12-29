<?php
/*
 * https://github.com/RobinHerbots/Inputmask
 */
namespace santilin\churros\widgets;

use yii\helpers\Html;
use yii\web\View;
use yii\widgets\MaskedInput;
use santilin\churros\helpers\DateTimeEx;

class QuickDateInput extends MaskedInput
{
	private $orig_id;
	public $format;

	public function init()
    {
		if( empty($this->mask) ) {
			$this->mask = $this->formatToMask();
		}
        parent::init();
        $this->clientOptions = array_merge([
			"insertMode" => false,
			'positionCaretOnClick' => 'select',
			'positionCaretOnTab' => 'select',
			'placeHolder' => $this->formatToPlaceHolder(),
		], $this->clientOptions);
        $this->orig_id = $this->options['id'];
        $this->options['id'] = $this->orig_id . "_date_disp";
	}


	protected function formatToMask()
	{
		return strtr( $this->format, [
			'd' => '99',
			'm' => '99',
			'y' => '99',
			'Y' => '99[99]',
		]);
	}

	protected function formatToPlaceHolder()
	{
		return strtr( $this->format, [
			'd' => '__',
			'm' => '__',
			'y' => '__',
			'Y' => '____',
		]);
	}

    protected function renderInputHtml($type)
    {
		$hid_options = [ 'id' => $this->orig_id ];
        if ($this->hasModel()) {
			if( empty($this->options['value']) ) {
				$value = Html::getAttributeValue($this->model, $this->attribute);
				if( !empty($value) ) {
					$date = null;
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
					if( $date != null ) {
						$value = $date->format(self::parseFormat(\Yii::$app->formatter->dateFormat, 'date'));
					}
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

// https://stackoverflow.com/questions/60759006/is-there-a-way-to-prevent-the-date-object-in-js-from-overflowing-days-months
function dateInputParseSpanishDate(datestr)
{
	var datestr_parts = datestr.split('/');
	if( datestr_parts.length == 3 ) {
		var year = parseInt(datestr_parts[2]);
		var month = parseInt(datestr_parts[1]);
		var day = parseInt(datestr_parts[0]);
		if( isNaN(year) ) {
			year = 0;
		}
		if( isNaN(month) ) {
			month == 0;
		}
		if( isNaN(day) ) {
			day = 0;
		}
		if( year == 0 && month == 0 && day == 0 ) {
			return null;
		}
		if( year == 0 ) {
			year = new Date().getFullYear();
		} else if (year<100) {
			year += 2000;
		}
		if( month == 0 ) {
			month = new Date().getMonth() + 1;
		} else if (month > 12 ) {
			return false;
		}
		var d = new Date(year, month-1, day);
		if( d.getFullYear() != year || d.getMonth() != month-1 || d.getDate() != day ) {
			return false;
		} else {
			return d;
		}
	} else {
		return null;
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
	if( date_js === null ) { // empty
		$('#$orig_id' ).val( '' );
		return true;
	 } else if (date_js == false ) { // wrong
		old_color = date_input.style.color;
		$('#$orig_id' ).val( date_input.value );
		date_input.classList.add('.invalid');
		setTimeout(function() {
			date_input.focus();
// 			date_input.classList.remove('.invalid');
		}, 2000);
		return false;
	} else {
		date_input.value = dateToSpanishFormat( date_js );
		$('#$orig_id').val( dateToSQLFormat( date_js ) );
		return true;
	}
}


$('#$id').closest('form').submit( function(e) {
	const el = $('#$id')[0];
	if( !dateInputChange(el, '$id') ) {
		e.preventDefault();
		return false;
	} else {
		return true;
	}
});
$('#$id').change( function() { dateInputChange(this, '$id'); } );
$('#$id').blur( function() { dateInputChange(this, '$id'); } );
$('#$id').focus( function() { dateInputSetSelectionRange(this,0,0); } );

EOF;
        $view->registerJs($js, View::POS_END, 'QuickDateInputJS');
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
