<?php

namespace santilin\churros\widgets;
use yii\helpers\{ArrayHelper,Html};
use santilin\churros\helpers\FormHelper;

trait ActiveFormTrait
{
	public $fieldsLayout;
	public $formLayout;

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
	}

	private function getBoostrapColumnClasses(int $cols): string
	{
		switch( $cols ) {
		case 1:
			$col = $col_sm = $col_md = $col_lg = $col_xl = 12;
			break;
		case 2:
			$col = $col_md = 6;
			$col_sm = $col_lg = $col_xl = 4;
			break;
		case 3:
			$col = $col_md = 4;
			$col_sm = $col_lg = $col_xl = 4;
			break;
		case 4:
		default:
			$col = $col_md = 3;
			$col_sm = $col_lg = $col_xl = 6;
		}
		return "col-$col col-sm-$col_sm col-md-$col_md col-lg-$col_lg col-xl-$col_xl";
	}

	private function addLayoutClasses(array &$fields_cfg, array $fields_in_row, string $fields_layout = '1col'): void
	{
		$ret = '';
		foreach($fields_in_row as $rlk => $row_layout ) {
			$layout = $row_layout['layout']??$fields_layout;
			switch ($row_layout['type']) {
			case 'container':
				$this->addLayoutClasses($fields_cfg, $row_layout['content'], $layout);
				break;
			case 'fields':
				foreach( $row_layout['fields'] as $fldname ) {
					if (!isset($fields_cfg[$fldname])) {
						$fields_cfg[$fldname] = $this->getFieldClasses($layout);
					} else {
						if (isset($fields_cfg[$fldname]['layout'])) {
							$fld_layout = $fields_cfg[$fldname]['layout'];
							unset($fields_cfg[$fldname]['layout']);
							$fields_cfg[$fldname] = array_merge(
								$this->getFieldClasses($layout,$fld_layout),
								$fields_cfg[$fldname]);
						}
					}
				}
			}
		}
	}

	public function layoutFields(array $layout_fields, array $form_fields): string
	{
		$ret = '';
		foreach($layout_fields as $rlk => $row_layout ) {
			$layout = $row_layout['layout']??'1col';
			$cols = intval($layout)?:1;
			$type = $row_layout['type']??'field';
			switch ($type) {
			case 'container':
				$ret .= '<div class="row">';
				foreach ($row_layout['content'] as $kc=>$container) {
					$ret .= '<div class="' . $this->getBoostrapColumnClasses($cols) . '">';
// 					$ret .= "<h1>$kc container</h1>";
					$ret .= $this->layoutFields([$container], $form_fields);
					$ret .= "</div>\n";
				}
				$ret .= "</div><!--container[$kc]-->";
				break;
			case 'fields':
			case 'fieldset':
				$nf = 0;
				$fs = '';
				foreach( $row_layout['fields'] as $form_field ) {
					if( !empty($form_fields[$form_field])) {
						if( ($nf%$cols) == 0) {
							if( $nf != 0 ) {
								$fs .= '</div><!--row-->';
							}
							$fs .= "\n" . '<div class="row">';
						}
						$fs .= '<div class="'
							. $this->getBoostrapColumnClasses($cols)
							. '">';
						$fs .= $form_fields[$form_field];
						$fs .= '</div>';
						$nf++;
					}
				}
				$fs .= '</div><!--row-->';
				if( isset($row_layout['title']) || $type == 'fieldset' ) {
					$legend = Html::tag('legend', $row_layout['title']);
					$ret .= Html::tag('fieldset', $legend . $fs, array_merge( ['id' => $this->options['id'] . "_layout_$rlk" ], $row_layout['options']??[]) );
				} else {
					$ret .= $fs;
				}
				break;
			case 'buttons':
				$classes = static::FIELD_HORIZ_CLASSES[$layout??'1col']['large']['horizontalCssClasses'];
				$ret .= '<div class="mt-2 clearfix row">';
				$ret .= "<div class=\"{$classes['wrapper']}\">";
				$ret .= $this->layoutButtons($row_layout['buttons'], $layout??$this->formLayout, $row_layout['options']??[]);
				$ret .= '</div><!--buttons -->' .  "\n";
				$ret .= '</div><!--row-->';
				break;
				foreach( $row_layout['fields'] as $fldname ) {
					if( array_key_exists($fldname, $form_fields)) {
						$ret .= $form_fields[$fldname];
					}
				}
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

	protected function layoutButtons(array $buttons, string $layout, array $options = []): string
	{
		$offset = static::FIELD_HORIZ_CLASSES[$layout]['large']['horizontalCssClasses']['offset'];
		if( is_array($offset) ) {
			$offset = implode(' ', $offset);
		}
		$wrapper = static::FIELD_HORIZ_CLASSES[$layout]['large']['horizontalCssClasses']['wrapper'];
		if( is_array($wrapper) ) {
			$wrapper = implode(' ', $wrapper);
		}
		$buttons = FormHelper::displayButtons($buttons);
		Html::addCssClass($options, $offset . ' ' . $wrapper);
		return <<<html
<div class="{$options['class']}">
$buttons
</div><!--buttons form-group-->
html;
	}


} // form
