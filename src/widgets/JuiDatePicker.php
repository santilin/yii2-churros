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
class JuiDatePicker extends InputWidget
{
    public string $displayFormat = 'd/m/Y';
    public string $dbFormat = 'Y-m-d';
    public string $language = 'es';
    public array $clientOptions = [];
    public array $inputOptions = [];

    public function run()
    {
        // Register jQuery UI assets for datepicker
        \yii\jui\JuiAsset::register($this->view);
        $idHidden = $this->options['id'];
        $idVisible = $idHidden . '_display';

        // Display value conversion (db to user format)
        $valueHidden = $this->hasModel() ? Html::getAttributeValue($this->model, $this->attribute) : $this->value;
        $valueVisible = '';
        if ($valueHidden) {
            $date = \DateTime::createFromFormat($this->dbFormat, $valueHidden);
            if ($date) {
                $valueVisible = $date->format($this->displayFormat);
            }
        }

        // Render hidden and visible inputs
        $output = Html::activeHiddenInput($this->model, $this->attribute, ['id' => $idHidden]);
        $output .= Html::textInput(null, $valueVisible, array_merge([
            'id' => $idVisible,
            'class' => 'form-control',
            'autocomplete' => 'off',
            'placeholder' => 'Selecciona la fecha',
        ], $this->inputOptions));

        // Prepare merged JS options
        $clientOptions = array_merge([
            'dateFormat' => str_replace(['Y', 'm', 'd'], ['yy', 'mm', 'dd'], $this->displayFormat),
            'changeMonth' => true,
            'changeYear' => true,
            'autoSize' => true,
            'onSelect' => new \yii\web\JsExpression(<<<JS

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

        return $output;
    }
}
