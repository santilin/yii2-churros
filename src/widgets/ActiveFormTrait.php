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
		$widget->horizontalCssClasses = $classes['horizontalCssClasses'];
		$widget->wrapperOptions = $classes['horizontalCssClasses']['wrapper'];
		$widget->labelOptions = $classes['horizontalCssClasses']['label'];
		return $widget->render();
	}

	public function fixFieldsLayout(array &$fields_cfg, array $render_fields, array $buttons = []): void
	{
		return;
		$form_layout = $this->layout;
		switch ($form_layout) {
			case 'inline':
				break;
			case 'horizontal':
				if (empty($this->fieldsLayout)) {
					$form_layout = '1col';
				} else if (is_string($this->fieldsLayout)) {
					$form_layout = $this->fieldsLayout;
					$this->fieldsLayout = null;
				}
				break;
			default:
				$this->layout = 'horizontal';
				break;
		}
		if (empty($this->fieldsLayout)) {
			$this->fieldsLayout = [];
			$this->fieldsLayout[] = [
				'type' => 'fields',
				'fields' => $render_fields,
				'layout' => $form_layout,
			];
			$this->fieldsLayout[] = [
				'type' => 'buttons',
				'buttons' => $buttons,
				'layout' => $form_layout,
			];
			if ($this->layout != 'inline') {
				$this->addLayoutClasses($fields_cfg, $this->fieldsLayout);
			}
		} else {
			$this->addLayoutClasses($fields_cfg, $this->fieldsLayout);
		}
		// check there are no render_fields with incorrect settings
		foreach ($fields_cfg as $kf => $fldcfg_info) {
			if (isset($fields_cfg[$kf]['layout'])) {
				unset($fields_cfg[$kf]['layout']);
			}
		}
	}



} // form
