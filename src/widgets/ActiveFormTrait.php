<?php

namespace santilin\churros\widgets;
use yii\helpers\{ArrayHelper,Html};
use santilin\churros\helpers\FormHelper;

trait ActiveFormTrait
{
	public $fieldsLayout;
	public $formLayout;

	public function layoutForm($form_fields, array $buttons = []): string
	{
		if( $this->formLayout == 'inline' || ($this->formLayout == '' && $this->layout == 'inline') ) {
			$this->formLayout = 'inline';
		}
		// horizontal layout
		if( empty($this->fieldsLayout) ) {
			$layout_parts = explode(':', $this->formLayout);
			$buttons_up = in_array('buttons_up', $layout_parts);
			$this->fieldsLayout = [];
			if( $buttons_up ) {
				$this->fieldsLayout[] = [ 'type' => 'buttons', 'buttons' => $buttons, 'layout' => '1col', 'options' => ['class' => 'mb-2'] ];
			}
			$this->fieldsLayout[] = [ 'type' => $layout_parts[0], 'fields' => array_keys($form_fields) ];
			if( !$buttons_up ) {
				$this->fieldsLayout[] = [ 'type' => 'buttons', 'buttons' => $buttons, 'layout' => '1col' ];
			}
		}
		return $this->layoutFields($this->fieldsLayout, $form_fields);
	}

	protected function layoutFields(array $rows_layout, array $form_fields,
		array $buttons = []): string
	{
		$ret = '';
		foreach($rows_layout as $lk => $layout ) {
			switch( $layout['type'] ) {
			case 'buttons':
				$ret .= '<div class="clearfix row">';
				$ret .= $this->layoutButtons($layout['buttons'], $layout['layout']??$this->formLayout, $layout['options']??[]);
				$ret .= '</div><!-- buttons -->' .  "\n";
				break;
			case '1col':
			case '1cols':
				foreach( $layout['fields'] as $fldname ) {
 					$this->setFieldClasses($form_fields, $fldname, $layout['type']);
					if( !empty($form_fields[$fldname])) {
						$ret .= $form_fields[$fldname];
					}
				}
				break;
			case '2col':
			case '2cols':
			case '3col':
			case '3cols':
			case '4col':
			case '4cols':
				$cols = intval(substr($layout['type'],0,1));
				switch( $cols ) {
				case 2:
					$col_sm = 6;
					break;
				case 3:
					$col_sm = 4;
					break;
				case 4:
				default:
					$col_sm = 3;
				}
				$nf = 0;
				foreach( $layout['fields'] as $form_field ) {
					if( !empty($form_fields[$form_field])) {
						$this->setFieldClasses($form_fields, $form_field, $layout['type']);
						if( ($nf%$cols) == 0) {
							if( $nf != 0 ) {
								$ret .= '</div>';
							}
							$ret .= "\n" . '<div class="row">';
						}
						$ret .= "<div class=\"col-sm-$col_sm\">";
						$ret .= $form_fields[$form_field];
						$ret .= '</div>';
						$nf++;
					}
				}
				if( ($nf%$cols) != 0) {
					$ret .= '</div>';
				}
				break;
			case 'tabs':
				break;
			case 'fieldset':
				if( isset($layout['title']) ) {
					$legend = Html::tag('legend', $layout['title']);
				} else {
					$legend = '';
				}
				$ret .= Html::tag('fieldset', $legend . $this->layoutFields($layout['layout'], $form_fields),
					array_merge( ['id' => $this->options['id'] . "_layout_$lk" ], $layout['options']??[]) );
				break;
			case 'subtitle':
				$ret .= $this->layoutContent(null, $layout['title'], $layout['options']??[]);
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
		return self::FIELD_HORIZ_CLASSES[$field_layout][$row_layout];
	}

	protected function layoutButtons(array $buttons, string $layout, array $options = []): string
	{
		$offset = static::FIELD_HORIZ_CLASSES['default'][$layout]['horizontalCssClasses']['offset'];
		if( is_array($offset) ) {
			$offset = implode(' ', $offset);
		}
		$wrapper = static::FIELD_HORIZ_CLASSES['default'][$layout]['horizontalCssClasses']['wrapper'];
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
