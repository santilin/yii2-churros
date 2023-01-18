<?php

namespace santilin\churros\widgets;
use yii\helpers\{ArrayHelper,Html};

trait ActiveFormTrait
{
	public $fieldsLayout;

	public function layoutForm($form_fields, array $buttons = []): string
	{
		if( empty($this->fieldsLayout) || $this->fieldsLayout === 'inline' ) {
			$this->fieldsLayout = "1col";
		}
		if( is_string($this->fieldsLayout) ) {
			// If not an array, tranform string to array
			$layout_parts = explode(':', $this->fieldsLayout);
			$buttons_up = in_array('buttons_up', $layout_parts);
			$layout = $layout_parts[0];
			$this->fieldsLayout = [];
			if( $buttons_up ) {
				$this->fieldsLayout[] = [ 'type' => 'buttons', 'buttons' => $buttons ];
			}
			$this->fieldsLayout[] = [ 'type' => $layout, 'fields' => array_keys($form_fields) ];
			if( !$buttons_up ) {
				$this->fieldsLayout[] = [ 'type' => 'buttons', 'buttons' => $buttons ];
			}
		}
		return $this->layoutFields($this->fieldsLayout, $form_fields, $buttons);
	}

	protected function layoutFields(array $form_layout, array $form_fields, array $buttons = []): string
	{
		$ret = '';
		foreach($form_layout as $lk => $layout ) {
			switch( $layout['type'] ) {
			case 'buttons':
				$ret .= '<div class="clearfix row">';
				$ret .= '<div class="' . implode(',', (array)self::FIELD_HORIZ_CLASSES['default']['1col']['horizontalCssClasses']['offset']) . '">';
				if( empty($layout['buttons']) ) {
					$ret .= $this->layoutButtons($buttons);
				} else {
					$layout_buttons = [];
					foreach( $buttons as $bkey => $b ) {
						$layout_buttons[$bkey] = $b;
					}
					$ret .= $this->layoutButtons($layout_buttons);
				}
				$ret .= '</div></div><!-- buttons -->' .  "\n";
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
// 		Html::addCssClass($config['labelOptions'], $cssClasses['label']);
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

} // form
