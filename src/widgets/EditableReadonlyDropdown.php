<?php

namespace santilin\churros\widgets;

use yii\bootstrap5\InputWidget;
use yii\helpers\Html;

class EditableReadonlyDropdown extends InputWidget
{
    public $model;
    public $attribute;
    public $items = [];
    public $options = [];

    /**
     * @inheritdoc
     */
    public function run()
    {
        $id = $this->options['id']?? Html::getInputId($this->model, $this->attribute);

        $buttonId = $id . '-edit-btn';
        $hiddenId = $id . '-hidden';
        // Hidden field for form submission
        echo Html::activeHiddenInput($this->model, $this->attribute, ['id' => $hiddenId] );

        // Input group start
        echo '<div class="input-group">';
        // Dropdown (readonly by default)
        echo Html::activeDropDownList($this->model, $this->attribute, $this->items, array_merge([
            'class' => 'form-select',
            'disabled' => true,
            'id' => $id,
        ], $this->options));
        // Edit button as input group addon
        echo '<button class="btn btn-outline-secondary input-group-btn" type="button" id="' . $buttonId . '">Edit</button>';
        echo '</div>';


        // JS to enable dropdown on button click
        $js = <<<JS
document.getElementById('$buttonId').addEventListener('click', function() {
    console.log(document.getElementById('$id'), '$id');
    if (!this.form._hasChanged) {
        dropdown = document.getElementById('$id');
        dropdown.disabled = false;
        window.yii.ChurrosForm.disableAllFieldsButOne(dropdown);
    }
    this.style.display = 'none';
});
JS;
        $this->getView()->registerJs($js);
    }
}
