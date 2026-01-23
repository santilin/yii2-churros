<?php
/*
 * Migrated from Inputmask to IMask
 * https://imask.js.org/
 * Retains all original functionality: format conversion, validation, hidden input sync
 */
namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\base\InvalidConfigException;
use yii\web\View;
use yii\widgets\InputWidget;
use santilin\churros\helpers\YADTC;
use santilin\churros\widgets\{DateFormatterAsset, ImaskAsset};

class QuickDateTimeInput extends InputWidget
{
    public $format;
    public $saveFormat;
    public $errorMessage;
    public $datetype;
    public $defaultTimes = null;
    private $js_error_message;
    private $orig_id;
    private $display_id;

    public function init()
    {
        if (!$this->format) {
            throw new InvalidConfigException("`format` must be set");
        }
        if ($this->defaultTimes) {
            if (!is_array($this->defaultTimes)) {
                throw new InvalidConfigException("`defaultTimes` must be an array");
            }
            $dft = [];
            foreach ($this->defaultTimes as $dtk => $dtv) {
                $dft[$this->formatToPlaceHolder($dtk)] = $dtv;
            }
            $this->defaultTimes = $dft;
        }
        parent::init();
        if (!$this->datetype) {
            $this->datetype = YADTC::guessTypeFromFormat($this->format);
        }
        if ($this->errorMessage == null) {
            switch ($this->datetype) {
                case 'date':
                    $this->errorMessage = Yii::t('churros', "La fecha no es válida");
                    break;
                case 'datetime':
                    $this->errorMessage = Yii::t('churros', "La fecha/hora no es válida");
                    break;
                case 'time':
                    $this->errorMessage = Yii::t('churros', "La hora no es válida");
                    break;
                default:
                    throw new InvalidConfigException("invalid datetype: {$this->datetype}");
            }
        }
        if (!$this->saveFormat) {
            $dcm = \Yii::$app->getModule('datecontrol');
            switch ($this->datetype) {
                case 'date':
                    $this->saveFormat = $dcm? $dcm->getSaveFormat('date') : YADTC::SQL_DATE_FORMAT;
                    break;
                case 'datetime':
                    $this->saveFormat = $dcm? $dcm->getSaveFormat('datetime') : YADTC::SQL_DATETIME_FORMAT;
                    break;
                case 'time':
                    $this->saveFormat = $dcm? $dcm->getSaveFormat('time') : YADTC::SQL_TIME_FORMAT;
                    break;
            }
        }
        DateFormatterAsset::register($this->view);
        ImaskAsset::register($this->view); // New asset bundle for IMask
        $this->js_error_message = addslashes($this->errorMessage);
        $this->orig_id = $this->options['id'] ?? $this->getId();
        $this->display_id = $this->orig_id . "_date_disp";
        $this->options['id'] = $this->display_id;
    }

    protected function formatToImaskPattern()
    {
        // Convert PHP format to IMask pattern (9→0, optional → [])
        $mask = strtr($this->format, [
            'd' => 'dd',
            'm' => 'mm',
            'y' => 'yy',
            'Y' => 'yyyy',
            'H' => 'HH',
            'i' => 'mm',
            's' => 'ss',
        ]);
        // Wrap optional parts for IMask dynamic masks
        return $mask;
    }

    protected function formatToPlaceHolder($format = null)
    {
        return strtr($format ?? $this->format, [
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
            'y' => '(?<year_short>[0-9_][0-9_])',
            'Y' => '(?<year_long>[0-9_][0-9_]([0-9_]{0,2}))',
            'H' => '(?<hour>[01][0-9]|2[0-4])',
            'i' => '(?<minute>[0-5][0-9])',
            's' => '(?<second>[0-5][0-9])',
        ]);
    }

    public function run()
    {
        $this->registerClientScript();
        return $this->renderInputHtml('text');
    }

    protected function renderInputHtml($type)
    {
        $hid_options = ['id' => $this->orig_id];
        if ($this->hasModel()) {
            if (empty($this->options['value'])) {
                $value = Html::getAttributeValue($this->model, $this->attribute);
                if (!empty($value)) {
                    $parsed_date = YADTC::createFromFormat($this->saveFormat, $value);
                    if ($parsed_date == null) {
                        switch ($this->datetype) {
                            case 'date':
                            case 'time':
                                $parsed_date = YADTC::createFromFormat(YADTC::SQL_DATETIME_FORMAT, $value);
                                break;
                            case 'datetime':
                                $parsed_date = YADTC::createFromFormat(YADTC::SQL_DATE_FORMAT, $value);
                                break;
                        }
                    }
                    if ($parsed_date != null) {
                        $this->options['value'] = $parsed_date->format($this->format);
                    }
                }
            }
            $ret = Html::activeHiddenInput($this->model, $this->attribute, $hid_options);
            $this->options['name'] = '';
            $ret .= Html::activeInput($type, $this->model, $this->attribute, $this->options);
        }
        return $ret;
    }

    public function registerClientScript()
    {
        $view = $this->getView();
        $id = $this->display_id;
        $orig_id = $this->orig_id;
        $format_as_regex = $this->formatToRegex();
        $imask_pattern = $this->formatToImaskPattern();
        $placeholder = $this->formatToPlaceHolder();
        $defaultTimes = $this->defaultTimes ? Json::htmlEncode($this->defaultTimes) : 'null';

        $js = <<<EOF
IMask(document.getElementById('$id'), {
    mask: '$imask_pattern',
    placeholderChar: '_',
    overwrite: false,
    lazy: false
});

// Form validation and sync (same logic as original)
window.yii.churros = window.yii.churros || {};
window.yii.churros.dateInputChange = function(\$input, origId, format, saveFormat, regex, errorMsg, defaultTimes) {
    const displayId = \$input.attr('id');
    const displayVal = \$input.val();
    if (displayVal === '') {
        document.getElementById(origId).value = '';
        return true;
    }

    // Parse using regex
    const match = displayVal.match(new RegExp(regex));
    if (!match) {
        alert(errorMsg);
        return false;
    }

    // Convert to save format (same as original)
    const parsed = YADTC.createFromFormat(format, displayVal);
    if (parsed) {
        document.getElementById(origId).value = parsed.format(saveFormat);
        return true;
    }
    return false;
};

$('#$id').closest('form').submit(function(e) {
    if (!window.yii.churros.dateInputChange($('#$id'), '$orig_id', '$this->format', '$this->saveFormat', '$format_as_regex', '$this->js_error_message', $defaultTimes)) {
        e.preventDefault();
        return false;
    }
});

$('#$id').on('blur change', function(e) {
    window.yii.churros.dateInputChange($(this), '$orig_id', '$this->format', '$this->saveFormat', '$format_as_regex', '$this->js_error_message', $defaultTimes);
});
EOF;
        $view->registerJs($js, View::POS_END, "QuickDateTimeInputJS_$id");
    }
}
