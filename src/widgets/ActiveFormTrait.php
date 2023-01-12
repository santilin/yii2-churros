<?php

namespace santilin\churros\widgets;
use yii\helpers\{ArrayHelper,Html};

trait ActiveFormTrait
{
	public $fieldsLayout;

	public function layoutForm($form_fields, array $buttons = []): string
	{
		$ret = '';
		if( empty($this->fieldsLayout) || $this->fieldsLayout == "1col" ) {
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
				foreach( $layout['fields'] as $form_field ) {
					if( !empty($form_fields[$form_field])) {
						$ret .= '<div class="row">';
						$ret .= '<div class="col-sm-12">';
						$ret .= $form_fields[$form_field];
						$ret .= '</div>';
						$ret .= '</div>';
					}
				}
				break;
			case '2col_rows':
				$nf = 0;
				foreach( $layout['fields'] as $form_field ) {
					if( !empty($form_fields[$form_field])) {
						if( $nf == 0 ) {
							$nf = 1;
							$ret .= '<div class="row">';
							$ret .= '<div class="col-sm-6">';
							$ret .= $form_fields[$form_field];
							$ret .= '</div>';
						} else {
							$nf = 0;
							$ret .= '<div class="col-sm-6">';
							$ret .= $form_fields[$form_field];
							$ret .= '</div>';
							$ret .= '</div>';
						}
					}
				}
				if( $nf == 1 ) {
					$ret .= '</div>';
				}
				break;
// 			case 3:
// 				$ret .= '<div class="row">';
// 				$ret .= '<div class="col-sm-4">';
// 				$ret .= $form_fields[$lrow[0]];
// 				$ret .= '</div>';
// 				$ret .= '<div class="col-sm-4">';
// 				$ret .= $form_fields[$lrow[1]];
// 				$ret .= '</div>';
// 				$ret .= '<div class="col-sm-4">';
// 				$ret .= $form_fields[$lrow[2]];
// 				$ret .= '</div>';
// 				$ret .= '</div>';
// 				break;
// 			case 4:
// 				$ret .= '<div class="row">';
// 				$ret .= $form_fields[$lrow[0]];
// 				$ret .= $form_fields[$lrow[1]];
// 				$ret .= $form_fields[$lrow[2]];
// 				$ret .= $form_fields[$lrow[3]];
// 				$ret .= '</div>';
// 				break;
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
