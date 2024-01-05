<?php

namespace santilin\churros\widgets;
use yii\helpers\{ArrayHelper,Html};
use santilin\churros\helpers\FormHelper;

trait ActiveFormTrait
{
	public $fieldsLayout;
	public $formLayout;

	public function getInputId($model, string $attribute): string
	{
		return Html::getInputId($model, $attribute);
	}

	public function fixFieldsLayout(array &$fields_cfg, array $render_fields, array $buttons = []): void
	{
		if ($this->formLayout == '' && $this->layout == 'inline') {
			$this->formLayout = 'inline';
			$layout_parts = ['1col'];
		} else { // horizontal layout
			$layout_parts = explode(':', $this->formLayout);
		}
		if( empty($this->fieldsLayout) ) {
			$buttons_up = in_array('buttons_up', $layout_parts);
			$this->fieldsLayout = [];
			if( $buttons_up ) {
				$this->fieldsLayout[] = [
					'type' => 'buttons',
					'buttons' => $buttons,
					'layout' => $layout_parts[0],
					'options' => ['class' => 'mb-2']
				];
			}
			$this->fieldsLayout[] = [
				'type' => 'fields',
				'fields' => $render_fields,
				'layout' => $layout_parts[0]
			];
			if( !$buttons_up ) {
				$this->fieldsLayout[] = [
					'type' => 'buttons',
					'buttons' => $buttons,
					'layout' => $layout_parts[0],
				];
			}
		}
		$this->addLayoutClasses($fields_cfg, $this->fieldsLayout);
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
	public function layoutFields(array $layout_rows, array $form_fields): string
	{
		$ret = '';
		foreach($layout_rows as $lrk => $row_layout ) {
			$layout = $row_layout['layout']??'1col';
			$cols = intval($layout)?:1;
			$type = $row_layout['type']??'fields';
			switch ($type) {
			case 'container':
				$ret .= '<div class="row">';
				foreach ($row_layout['content'] as $kc => $container) {
					$ret .= '<div class="' . FormHelper::getBoostrapColumnClasses($cols) . '">';
					$ret .= $this->layoutFields([$container], $form_fields);
					$ret .= "</div>\n";
				}
				$ret .= "</div><!--container[$kc]-->";
				break;
			case 'fields':
			case 'fieldset':
				$nf = 0;
				$fs = '';
				foreach( $row_layout['fields'] as $attribute => $form_field ) {
					if( !empty($form_fields[$form_field])) {
						if( ($nf%$cols) == 0) {
							if( $nf != 0 ) {
								$fs .= '</div><!--row-->';
							}
							$fs .= "\n" . '<div class="row">';
						}
						$fs .= '<div class="'
							. FormHelper::getBoostrapColumnClasses($cols)
							. '">';
						$fs .= $form_fields[$form_field];
						$fs .= '</div>';
						$nf++;
					}
				}
				$fs .= '</div><!--row-->';
				if( isset($row_layout['title']) && $type == 'fieldset' ) {
					$legend = Html::tag('legend', $row_layout['title'], $row_layout['title_options']??[]);
					$ret .= Html::tag('fieldset', $legend . $fs, array_merge( ['id' => $this->options['id'] . "_layout_$lrk" ], $row_layout['options']??[]) );
				} else if( isset($row_layout['title'])  ) {
					$legend = Html::tag('div', $row_layout['title'], $row_layout['title_options']??[]);
					$ret .= Html::tag('div', $legend . $fs, array_merge( ['id' => $this->options['id'] . "_layout_$lrk" ], $row_layout['options']??[]) );
				} else {
					$ret .= $fs;
				}
				break;
			case 'buttons':
				$classes = static::FIELD_HORIZ_CLASSES[$layout??'1col']['large']['horizontalCssClasses']['offset'];
				$ret .= '<div class="mt-2 clearfix row">';
				if (is_array($classes)) {
					$s_classes = implode(' ', $classes);
				}
				$ret .= "<div class=\"$s_classes\">";
				$ret .= $this->layoutButtons($row_layout['buttons'], $layout??$this->formLayout, $row_layout['options']??[]);
				$ret .= '</div><!--buttons -->' .  "\n";
				$ret .= '</div><!--row-->';
				break;
			case 'subtitle':
				$ret .= $this->layoutContent(null, $row_layout['title'], $row_layout['options']??[]);
				break;
			}
		}
		return $ret;
	}

	public function layoutContent(?string $label, string $content, array $options = []):string
	{
		$ret = '';
		$wrapper_options = [ 'class' => $this->fieldConfig['horizontalCssClasses']['wrapper'] ];
		if( isset($options['class']) ) {
			Html::addCssClass($wrapper_options, $options['class']);
		}
// 		Html::addCssClass($config['errorOptions'], $cssClasses['error']);
// 		Html::addCssClass($config['hintOptions'], $cssClasses['hint']);
// 		Html::addCssClass($config['options'], $cssClasses['field']);
		if( empty($label) ) {
			Html::addCssClass($wrapper_options, $this->fieldConfig['horizontalCssClasses']['offset']);
		}
		$wrapper_tag = ArrayHelper::remove($wrapper_options, 'tag', 'div');
		$ret .= Html::beginTag($wrapper_tag, $wrapper_options);
		$ret .= $content;
		$ret .= Html::endTag($wrapper_tag);
		return $ret;
	}

	public function getLayoutClasses($field_layout, $row_layout)
	{
		if( $field_layout == 'static' ) {
			return self::FIELD_HORIZ_CLASSES['static'];
		} else {
			return self::FIELD_HORIZ_CLASSES[$field_layout][$row_layout];
		}
	}

	public function layoutButtons(array $buttons, string $layout, array $options = []): string
	{
		$buttons = FormHelper::displayButtons($buttons);
		Html::addCssClass($options, 'form-buttons-group');
		return <<<html
<div class="{$options['class']}">
$buttons
</div><!--buttons form-group-->
html;
	}


} // form
