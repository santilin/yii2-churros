<?php

namespace santilin\churros\widgets;
use yii\base\InvalidConfigException;
use yii\helpers\{ArrayHelper,Html};
use yii\bootstrap5\Tabs;
use santilin\churros\helpers\{AppHelper,FormHelper};

trait ActiveFormTrait
{
	public $fieldsLayout;

	public function getInputId($model, string $attribute): string
	{
		return Html::getInputId($model, $attribute);
	}


	public function generateFieldsLayout(array $form_fields, array $buttons,
										 string $size = 'large', string $style = 'grid')
	{
		$layout = $this->layout;
		$add_buttons = $add_layout = false;
		if (is_array($this->fieldsLayout)) {
			if (!AppHelper::findKeyAndValueInArray($this->fieldsLayout, 'type', 'buttons')) {
				$add_buttons = true;
			}
		} else if (is_string($this->fieldsLayout)) {
			$layout = $this->fieldsLayout;
			$add_buttons = $add_layout = true;
		} else {
			$add_buttons = $add_layout = true;
		}
		if ($layout == 'horizontal') {
			$layout = '1col';
		}
		if ($add_layout) {
			$this->fieldsLayout = [
				[
					'type' => 'widgets',
					'layout' => $layout,
					'content' => array_keys($form_fields),
				],
			];
		}
		if ($add_buttons) {
			if (!empty($buttons)) {
				$this->fieldsLayout[] = [
					'type' => 'buttons',
					'layout' => '1col',
					'content' => $buttons
				];
			}
		}
	}

	public function layoutFields(array $form_fields, array $buttons, string $size = 'large', string $style = 'grid'): string
	{
		if (!is_array($this->fieldsLayout)) {
			$this->generateFieldsLayout($form_fields, $buttons, $size, $style);
		} else {
			if (!empty($buttons)) {
				if (!AppHelper::findKeyAndValueInArray($this->fieldsLayout, 'type', 'buttons')) {
					$this->fieldsLayout[] = [
						'type' => 'buttons',
						'layout' => '1col',
						'content' => $buttons
					];
				}
			}
		}
		$layer = new WidgetLayer($this->fieldsLayout, $form_fields, [$this, 'renderFormField'], self::FORM_FIELD_HORIZ_CLASSES);
		return $layer->layout('widgets', $this->layout, $size, $style);
	}

	public function renderFormField($widget, array $options, int $index)
	{
		return $widget->render();
	}

} // form
