<?php
namespace santilin\churros\yii;

use yii\helpers\Html;
use yii\widgets\MaskedInput;
use santilin\churros\helper\DateTimeHelper;

class DateInput extends MaskedInput
{

	private $orig_id;

	public function init()
    {
        parent::init();
        $this->orig_id = $this->options['id'];
        $this->options['id'] = $this->orig_id . "_date_disp";
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
						$timestamp = \DateTime::createFromFormat($dcm->getSaveFormat('date'), $value);
					} else {
						$timestamp = \DateTime::createFromFormat(DateTimeHelper::DATETIME_DATE_SQL_FORMAT, $value);
					}
					$value = $timestamp->format(self::parseFormat(\Yii::$app->formatter->dateFormat, 'date'));
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

    private static function addChange(&$options, $id)
    {
		if( !isset($options['onchange']) ) {
			$options['onchange'] = <<<EOF
var fecha_val = $(this).val();
debugger;
var fecha_parts = fecha_val.split('/');
var year = parseInt(fecha_parts[2]);
if( isNaN(year) || year == 0 ) {
	year = new Date().getFullYear();
} else if (year<100) {
	year += 2000;
}
var month = parseInt(fecha_parts[1]);
if( isNaN(month) || month == 0 ) {
	month = new Date().getMonth();
} else {
	--month
	if (month < 10 ) {
		month = "0" + month;
	}
}
var day = parseInt(fecha_parts[0]);
if( isNaN(day) || day == 0 ) {
	day = new Date().getDate();
} else if( day < 10 ) {
	day = "0" + day;
}
var fecha_js = new Date(year, month, day);
var fecha_sql = fecha_js.getFullYear() + "-" + (fecha_js.getMonth()+1) + "-" + fecha_js.getDate();
$('#$id').val(fecha_sql);
$(this).val(day + "/" + (++month) + "/" + year );
console.log($(this).val());
console.log($('#$id').val());
EOF;
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
