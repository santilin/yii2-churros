<?php
namespace santilin\churros\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\jui\DatePicker;
use yii\web\View;
use Yii;

/**
 * Widget que crea un campo DatePicker visible con formato personalizado,
 * sincronizando un campo oculto con fecha en formato SQL.
 */
class JuiDatePicker extends Widget
{
    public Model $model;
    public string $attribute;
    public string $language = '';
    public string $displayFormat = 'php:d/m/Y'; // Formato visual por defecto

    public function init(): void
    {
        parent::init();
        if ($this->language === '') {
            $this->language = substr(Yii::$app->language, 0, 2);
        }
    }

    public function run(): string
    {
        $attributeVisible = $this->attribute . '_visible';
        $attributeHidden = $this->attribute;

        $valueHidden = Html::getAttributeValue($this->model, $attributeHidden);
        $valueVisible = '';
        if ($valueHidden !== null && $valueHidden !== '') {
            // Eliminar PHP prefix de displayFormat para DateTime::format
            $format = str_replace('php:', '', $this->displayFormat);
            $date = \DateTime::createFromFormat('Y-m-d', $valueHidden);
            if ($date !== false) {
                $valueVisible = $date->format($format);
            }
        }

        $idHidden = Html::getInputId($this->model, $attributeHidden);
        $idVisible = Html::getInputId($this->model, $attributeVisible);

        $output = Html::activeHiddenInput($this->model, $attributeHidden, ['id' => $idHidden]);
        $output .= DatePicker::widget([
            'model' => $this->model,
            'attribute' => $attributeVisible,
            'language' => $this->language,
            'dateFormat' => $this->displayFormat,
            'value' => $valueVisible,
            'clientOptions' => [
                'changeMonth' => true,
                'changeYear' => true,
                'autoSize' => true,
                'onSelect' => new \yii\web\JsExpression(<<<JS

function(dateText, inst) {
	var parts = dateText.split('/');
	if(parts.length === 3) {
		var sqlDate = parts[2] + '-' + parts[1] + '-' + parts[0];
		$('#$idHidden').val(sqlDate);
	}
}
JS
                ),
            ],
            'options' => [
                'class' => 'form-control',
                'autocomplete' => 'off',
                'placeholder' => 'Selecciona la fecha',
                'id' => $idVisible,
            ],
        ]);

        return $output;
    }
}
