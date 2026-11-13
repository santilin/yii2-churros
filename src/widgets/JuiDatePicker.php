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
            // El visible siempre lleva el formato del datepicker (d/m/y), así
            // que basta con relanzar el change para que la función global de
            // abajo sincronice el hidden
            'onSelect' => new \yii\web\JsExpression(<<<JS
function(dateText, inst) {
    $('#$idVisible').trigger('change');
}
JS
            ),
        ], $this->clientOptions);

        // Encode and register JS
        $optionsJson = Json::htmlEncode($clientOptions);
        $this->view->registerJs(<<<JS

if (!window.churrosJuiSync) {
    // Sincroniza el hidden SQL (id_value) con el visible (d/m/y).
    // Un visible vacío deja el hidden vacío; un texto sin 3 partes
    // separadas por '/' no se toca.
    window.churrosJuiSync = function(id) {
        var dateText = $('#' + id).val();
        var parts = dateText.split('/');
        if (dateText === '') {
            $('#' + id + '_value').val('');
        } else if (parts.length === 3) {
            $('#' + id + '_value').val(parts[2] + '-' + parts[1] + '-' + parts[0]);
        }
    };
}
$('#$idVisible').datepicker($optionsJson).on('change blur', function() {
    window.churrosJuiSync('$idVisible');
});
// Los campos rellenados por otros scripts (p.ej. cascadas con .val())
// no disparan change/blur: al enviar el formulario se sincronizan igual
$('#$idVisible').closest('form').on('submit', function() {
    window.churrosJuiSync('$idVisible');
});
// On page load, sync the hidden field from the visible value
window.churrosJuiSync('$idVisible');
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
