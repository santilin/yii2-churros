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

	private function addLayoutClasses(array &$fields_cfg, array $fields_in_row, string $fields_layout = '1col'): void
	{

		$ret = '';
		foreach($fields_in_row as $lrk => $row_layout ) {
			$layout = $row_layout['layout']??$fields_layout;
			switch ($row_layout['type']) {
			case 'container':
				$this->addLayoutClasses($fields_cfg, $row_layout['content'], $layout);
				break;
			case 'fieldset':
			case 'fields':
				$nf = 0;
				foreach ($row_layout['fields'] as $fldname) {
					if (!isset($fields_cfg[$fldname])) {
						$fields_cfg[$fldname] = $this->fieldClasses($layout, 'large');
					} else {
						if (isset($fields_cfg[$fldname]['layout'])) {
							$fld_layout = $fields_cfg[$fldname]['layout'];
							unset($fields_cfg[$fldname]['layout']);
						} else {
							$fld_layout = 'large';
						}
						$fields_cfg[$fldname] = array_merge(
							$this->fieldClasses($layout,$fld_layout),
							$fields_cfg[$fldname]);
					}
					switch($row_layout['labels']??null) {
					case 'none':
						$fields_cfg[$fldname]['horizontalCssClasses']['label'][] = 'hidden';
						break;
					case 'vertical':
						$fields_cfg[$fldname]['horizontalCssClasses']['label']
							= $fields_cfg[$fldname]['horizontalCssClasses']['wrapper']
							= 'col-lg-12 col-md-12 col-sm-12 col-12 col-12';
					}
					if ($nf == 0 && !empty($row_layout['hide_first_label']) ) {
						$fields_cfg[$fldname]['horizontalCssClasses']['label'] = "hidden";
					}
					$nf++;
				}
			}
		}
	}

	/**
	 * Recursivelly lays out the fiels of a form
	 */
	public function layoutFields(array $form_fields, string $style = 'grid'): string
	{
		$layer = new WidgetLayer($this->fieldsLayout, $form_fields, [$this, 'setLayoutClasses'], self::FORM_FIELD_HORIZ_CLASSES);
		return $layer->layout('fields', $this->layout, $style);
	}

	public function setLayoutClasses($widget, array $classes, int $index)
	{
		$widget->horizontalCssClasses = $classes;
		return $widget->render();
	}

} // form
