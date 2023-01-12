<?php

namespace santilin\churros\widgets;
use yii\helpers\{ArrayHelper,Html};

trait ActiveFormTrait
{
	public $fieldsLayout;

	public function layoutForm($form_fields, array $buttons = []): string
	{
		$ret = '';
		if( empty($this->fieldsLayout) || $this->fieldsLayout == "1col" || $this->fieldsLayout == "inline" ) {
			foreach( $form_fields as $name => $code ) {
				$ret .= $form_fields[$name]. "\n";
			}
			$ret .= $this->layoutButtons($buttons);
			return $ret;
		} else if( $this->fieldsLayout == "1col_buttons_up" ) {
			$ret .= $this->layoutButtons($buttons);
			foreach( $form_fields as $name => $code ) {
				$ret .= $form_fields[$name]. "\n";
			}
			return $ret;
		} else if( $this->fieldsLayout == "4cols" ) {
			$this->fieldsLayout = [
				 [ 'type' => '4cols_rows', 'fields' => array_keys($form_fields) ]
			];
		}
		$ret .= $this->layoutFields($this->fieldsLayout, $form_fields, $buttons);
		return $ret;
	}

	protected function layoutFields(array $form_layout, array $form_fields, array $buttons = []): string
	{
		$ret = '';
		foreach($form_layout as $lk => $layout ) {
			switch( $layout['type'] ) {
			case 'buttons':
				if( empty($buttons['buttons']) ) {
					$ret .= $this->layoutButtons($buttons);
				} else {
					$layout_buttons = [];
					foreach( $buttons['buttons'] as $bkey => $b ) {
						$layout_buttons[$bkey] = $b;
					}
					$ret .= $this->layoutButtons($layout_buttons);
				}
				break;
			case '1col_rows':
			case '1cols_rows':
			case '2col_rows':
			case '2cols_rows':
			case '3col_rows':
			case '3cols_rows':
			case '4col_rows':
			case '4cols_rows':
				$cols = intval(substr($layout['type'],0,1));
				switch( $cols ) {
				case 1:
					$col_sm = 12;
					break;
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
					if( ($nf%$cols) == 0) {
						if( $nf != 0 ) {
							$ret .= '</div>';
						}
						$ret .= "\n" . '<div class="row">';
					}
					if( !empty($form_fields[$form_field])) {
						$ret .= "<div class=\"col-sm-$col_sm\">";
						$ret .= $form_fields[$form_field];
						$ret .= '</div>';
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



} // form
