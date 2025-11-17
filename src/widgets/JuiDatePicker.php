<?php
namespace santilin\churros\widgets;

use yii\widgets\InputWidget;
use yii\helpers\{Html, Json};
use yii\jui\DatePicker;
use yii\web\View;
use Yii;

/**
 * Widget que crea un campo DatePicker visible con formato personalizado,
 * sincronizando un campo oculto con fecha en formato SQL.
 */
class JuiDatePicker extends DatePicker
{
    public string $dbFormat = 'Y-m-d';

    public function run()
    {
        $idVisible = $this->options['id'];
        $idHidden = $idVisible . '_value';

        // Prepare merged JS options
        $clientOptions = array_merge([
            'dateFormat' => str_replace(['Y', 'm', 'd', 'php:'], ['yy', 'mm', 'dd', ''],
                                        $this->dateFormat),
            'changeMonth' => true,
            'changeYear' => true,
            'autoSize' => true,
            'onSelect' => new \yii\web\JsExpression(<<<JS
/// @todo make this valid for any language
function(dateText, inst) {
    console.log(dateText);
    var parts = dateText.split('/');
    if(parts.length === 3) {
        var sqlDate = parts[2] + '-' + parts[1] + '-' + parts[0];
        $('#$idHidden').val(sqlDate);
        console.log(sqlDate);
    }
}
JS
            ),
        ], $this->clientOptions);

        // Encode and register JS
        $optionsJson = Json::htmlEncode($clientOptions);
        $this->view->registerJs(<<<JS

$('#$idVisible').datepicker($optionsJson).on('change', function() {
    var dateText = $(this).val();
    console.log(dateText);
    var parts = dateText.split('/');
    if(parts.length === 3) {
        var sqlDate = parts[2] + '-' + parts[1] + '-' + parts[0];
        $('#$idHidden').val(sqlDate);
    }
});

// On page load, sync the hidden field if visible has value
if ($('#$idVisible').val() !== '') {
    var parts = $('#$idVisible').val().split('/');
    if(parts.length === 3) {
        var sqlDate = parts[2] + '-' + parts[1] + '-' + parts[0];
        $('#$idHidden').val(sqlDate);
    }
}
JS
        , View::POS_READY);

        return parent::run();
    }


    /**
     * Renders the DatePicker widget.
     * @return string the rendering result.
     */
    protected function renderWidget()
    {
        $contents = [];

        // get formatted date value
        if ($this->hasModel()) {
            $value = Html::getAttributeValue($this->model, $this->attribute);
        } else {
            $value = $this->value;
        }
        $value_disp = '';
        if ($value !== null && $value !== '') {
            // format value according to dateFormat
            try {
                $value_disp = Yii::$app->formatter->asDate($value, $this->dateFormat);
            } catch(\yii\base\InvalidArgumentException $e) {
                // ignore exception and keep original value if it is not a valid date
            }
        }
        $options = $this->options;

        if ($this->inline === false) {
            // render a text input
            $save_options = $options;
            unset($options['class']);
            $options['id'] .= '_value';
            if ($this->hasModel()) {
                $contents[] = Html::activeHiddenInput($this->model, $this->attribute, $options);
            } else {
                $contents[] = Html::hiddenInput($this->name, $value, $options);
            }
            $options = $save_options;
            $options['value'] = $value_disp;
            $options['name'] = Html::getInputName($this->model, $this->attribute . '_disp');
            if ($this->hasModel()) {
                $contents[] = Html::activeTextInput($this->model, $this->attribute, $options);
            } else {
                $contents[] = Html::textInput($this->name, $value, $options);
            }
            // $this->clientOptions['defaultDate'] = $value_disp;
            // $this->clientOptions['altField'] = '#' . $this->options['id'];
            $contents[] = Html::tag('div', null, $this->containerOptions);
        } else {
            // render an inline date picker with hidden input
            $options['value'] = $value;
            if ($this->hasModel()) {
                $contents[] = Html::activeHiddenInput($this->model, $this->attribute, $options);
            } else {
                $contents[] = Html::hiddenInput($this->name, $value, $options);
            }
            $this->clientOptions['defaultDate'] = $value;
            $this->clientOptions['altField'] = '#' . $this->options['id'];
            $contents[] = Html::tag('div', null, $this->containerOptions);
        }

        return implode("\n", $contents);
    }

}
