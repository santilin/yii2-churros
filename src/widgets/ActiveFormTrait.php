<?php

namespace santilin\churros\widgets;
use yii\base\InvalidConfigException;
use yii\helpers\{ArrayHelper,Html};
use yii\bootstrap4\Tabs;
use santilin\churros\helpers\FormHelper;

trait ActiveFormTrait
{
	public $fieldsLayout;

	public function getInputId($model, string $attribute): string
	{
		return Html::getInputId($model, $attribute);
	}

	public function layoutFields(array $form_fields, array $buttons, string $style = 'grid'): string
	{
		$layout = $this->layout;
		if (is_string($this->fieldsLayout)) {
			$layout = $this->fieldsLayout;
		}
		if ($layout == 'horizontal') {
			$layout = '1col';
		}
		if (empty($this->fieldsLayout) || is_string($this->fieldsLayout)) {
			$this->fieldsLayout = [
				[
					'type' => 'fields',
					'layout' => $layout,
					'content' => array_keys($form_fields),
				],
			];
			if (!empty($buttons)) {
				$this->fieldsLayout[] = [
					'type' => 'buttons',
					'layout' => '1col',
					'content' => $buttons
				];
			}
		}
		$layer = new WidgetLayer($this->fieldsLayout, $form_fields, [$this, 'renderFormField'], self::FORM_FIELD_HORIZ_CLASSES);
		return $layer->layout('fields', $this->layout, $style);
	}

	public function renderFormField($widget, array $classes, int $index)
	{
		if (is_array($classes['horizontalCssClasses']['label']??false)) {
			$classes['horizontalCssClasses']['label'] = implode(' ', $classes['horizontalCssClasses']['label']);
		}
		if (is_array($classes['horizontalCssClasses']['wrapper']['classes']??false)) {
			$classes['horizontalCssClasses']['wrapper'] = implode(' ', $classes['horizontalCssClasses']['wrapper']);
		}
		$widget->horizontalCssClasses = $classes['horizontalCssClasses'];
		$widget->wrapperOptions['class'] = $classes['horizontalCssClasses']['wrapper'];
		$widget->labelOptions['class'] = $classes['horizontalCssClasses']['label'];
		return $widget->render();
	}

} // form
