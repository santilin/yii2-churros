<?php
namespace santilin\churros\widgets;

use yii\bootstrap5\InputWidget;
use yii\helpers\Html;

class ReadonlyDropdown extends InputWidget
{
    /**
     * @var \yii\base\Model the data model that this widget is associated with
     */
    public $model;

    /**
     * @var string the model attribute that the dropdown list is associated with
     */
    public $attribute;

    /**
     * @var array the options for the dropdown list
     */
    public $items = [];

    /**
     * @var array the HTML options for the dropdown list
     */
    public $options = [];

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->renderHiddenField();
        $this->renderDropdown();
    }

    /**
     * Renders the disabled dropdown list.
     */
    protected function renderDropdown()
    {
        echo Html::activeDropDownList($this->model, $this->attribute, $this->items, array_merge([
            'class' => 'form-select',
            'disabled' => true,
        ], $this->options));
    }

    /**
     * Renders the hidden field with the real value.
     */
    protected function renderHiddenField()
    {
        echo Html::activeHiddenInput($this->model, $this->attribute);
	}
}
