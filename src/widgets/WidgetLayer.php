<?php

namespace santilin\churros\widgets;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\{ArrayHelper,Html};
use yii\bootstrap5\Tabs;
use santilin\churros\helpers\FormHelper;

class WidgetLayer
{
	protected array $widgets_used = [];

	public function __construct(
		protected array|string|null $widgetsLayout,
		protected array $widgets,
		protected $widget_painter,
		protected array $widget_layout_horiz_config)
	{
	}

	public function layout(string $type, string $form_layout = '1col',
						   string $size = 'large', string $style = 'grid'): string
	{
		if (empty($this->widgetsLayout)) {
			$this->widgetsLayout = [
				[
					'type' => $type,
					'layout' => $form_layout,
					'style' => $style,
					'content' => array_keys($this->widgets),
				]
			];
		} else if (is_string($this->widgetsLayout)) {
			$this->widgetsLayout = [
				[
					'type' => $type,
					'layout' => $this->widgetsLayout,
					'style' => $style,
					'content' => array_keys($this->widgets),
				]
			];
		}
		$this->widgets_used = [];
		$ret = $this->layoutWidgets($this->widgetsLayout, [
			'size' => $size,
			'style' => $style,
			'layout' => $form_layout,
		]);
		$not_used = array_diff(array_keys($this->widgets), $this->widgets_used);
		if (!empty($not_used)) {
			Yii::warning("Widgets in form not used in layout: " . json_encode($not_used));
		}
		$this->widgets_used = [];
		return $ret;
	}


	/**
	 * Recursivelly lays out the widgets
	 */
	protected function layoutWidgets(array $layout_row, array $parent_options = [], int|string $row_key = null): string
	{
		$has_parent_row = $parent_options['has_parent_row']??false;
		$has_parent_col = $parent_options['has_parent_col']??false;
		if (!isset($layout_row['content'])) {
			if (ArrayHelper::isIndexed($layout_row)) {
				$ak = array_keys($layout_row);
				$av = reset($layout_row);
				if (!is_array($av)) {
					$layout_row = [
						'type' => 'fields',
						'content' => $layout_row,
						'layout' => $parent_options['layout']??'1col',
						'size' => $parent_options['size']??'large',
						'has_parent_row' => $has_parent_row,
						'has_parent_col' => $has_parent_col,
					];
				} else {
					$layout_row = [
						'type' => 'container',
						'content' => $layout_row,
						'layout' => '1col',
						'size' => $parent_options['size']??'large',
						'style' => 'rows',
						'has_parent_row' => $has_parent_row,
						'has_parent_col' => $has_parent_col,
					];
				}
				return $this->layoutWidgets($layout_row, $parent_options, reset($ak));
			} else {
				if ($layout_row['layout']??'1col' == 'inline') {
					$cols = 10000;
				} else {
					$cols = intval($layout_row['layout']??'1'); // ?:max(count($layout_row['content']), 4);
				}
				$ret = ["<div class=\"row layout-$cols-cols\">"];
				if (!$has_parent_col) {
					$ret[] = '<div class="' . $this->columnClasses($cols) . '">';
				}
				foreach ($layout_row as $klr => $lr) {
					if ($lr === null) {
						continue;
					}
					$ret[] = $this->layoutWidgets($lr, [
						'type' => 'container',
						'style' => 'rows',
						'layout' => $parent_options['layout']??'1col',
						'size' => $parent_options['size']??'large',
						'has_parent_row' => true,
						'has_parent_col' => true,
					], $klr);
				}
				if (!$has_parent_col) {
					$ret[] = '</div>';
				}
				$ret[] = '</div>';
				return implode('', $ret);
			}
		}
		$layout_row_layout = $layout_row['layout'] ?? '1col';
		if (empty($layout_row['type'])) {
			if (is_array($layout_row['content'])) {
				$layout_row_type = 'container';
			} else {
				$layout_row_type = 'row';
			}
		} else {
			$layout_row_type = $layout_row['type'];
		}
		if (empty($layout_row['style'])) {
			if (strpos($layout_row_layout, 'col')) {
				$layout_row_style = 'cols';
			} else {
				$layout_row_style = 'rows';
			}
		} else {
			$layout_row_style = $layout_row['style'];
		}
		if (empty($layout_row['size'])) {
			$layout_row['size'] = $parent_options['size']??'large';
		}
		if ($layout_row_layout == 'inline') {
			$cols = 10000;
		} else {
			$cols = intval($layout_row_layout); // ?:max(count($layout_row['content']), 4);
		}
		$ret = '';
		if (!$has_parent_row) {
			$ret .= "<!--parent row--><div class=\"row layout-$cols-cols\">";
		}
		switch ($layout_row_type) {
		case 'container':
			switch ($layout_row_style) {
				case 'tabs':
					$ret .= "<!--tabs: $row_key--><div class=\"" . $this->columnClasses($cols) . '">';
					$tab_items = [];
					$has_active = false;
					foreach ($layout_row['content'] as $kc => $row_content) {
						if ($row_content === null) {
							continue;
						}
						if (!is_array($row_content)) {
							$row_content = [
								'label' => $kc,
								'content' => $row_content
							];
						}
						if ($row_content['active']??false == true) {
							$has_active = true;
						}
						$tab_items[] = [
							'label' => ArrayHelper::remove($row_content, 'title', $kc),
							'active' => ArrayHelper::remove($row_content, 'active', false),
							'headerOptions' => ArrayHelper::remove($row_content, 'headerOptions', []),
							'content' => $this->layoutWidgets($row_content, [
								'layout' => $layout_row_layout,
								'style' => $layout_row_style,
								'type' => $layout_row_type,
								'has_parent_row' => false, 'has_parent_col' => false
							], $kc),
						];
					}
					if (!$has_active && count($tab_items)) {
						$tab_items[0]['active'] = true;
					}
					$ret .= Tabs::widget(['items' => $tab_items, 'tabContentOptions' => $layout_row['htmlOptions']??[]]);
					$ret .= "</div><!--end tabs-->";
					break;
				case 'rows':
				case 'grid-nolabels':
					if (!$has_parent_col) {
						$ret .='<div class="' . $this->columnClasses($cols) . '">';
					}
					$ret .= '<!--rows-->';
					$rows_content = '';
					foreach ($layout_row['content'] as $kc => $row_content) {
						if (empty($row_content))  {
							continue;
						}
						$rows_content .= $this->layoutWidgets((array)$row_content, [
							'layout' => $layout_row_layout,
							'style' => $layout_row_style,
							'type' => $layout_row_type,
							'size' => $layout_row['size'],
							'has_parent_row' => true, 'has_parent_col' => true ], $kc);
					}
					$ret .= $rows_content;
					// $ret .= Html::tag('div', $rows_content, $layout_row['htmlOptions']??[]);
					if (!$has_parent_col) {
						$ret .= '</div>';
					}
					$ret .= '<!--end rows-->';
					break;
				case 'cols':
					// $cols = min(count($layout_row['content']), 4);
					$ret .= "<!--$cols cols-->";
					foreach ($layout_row['content'] as $kc => $row_content) {
						$row_options = $row_content['htmlOptions']??[];
						Html::addCssClass($row_options, $this->columnClasses($cols));
						$ret .= Html::tag('div',
							$this->layoutWidgets((array)$row_content, [
								'layout' => $layout_row_layout,
								'style' => $layout_row_style,
								'type' => $layout_row_type,
								'has_parent_row' => false, 'has_parent_col' => false], $kc),
							$row_options);
					}
					$ret .= "<!--end cols-->";
					break;
				default:
					throw new \Exception($layout_row_style . ': container style not valid');
			}
			$ret .= "<!--end container: $row_key-->";
			break;

		case 'widgets':
		case 'fields':
			$indexf = 0;
			$only_widget_names = true;
			foreach($layout_row as $lrk => $rl) {
				if (is_string($lrk) || is_array($rl)) {
					$only_widget_names = false;
					break;
				}
			}
			$row_html = '';
			if (!$has_parent_col) {
				$row_html.= '<div class="' . $this->columnClasses($cols) . '">';
			}
			if ($only_widget_names) {
				$layout_row = ['type' => $layout_row_type, 'content' => $layout_row, 'style' => 'rows'];
			}
			$subtitle = $layout_row['subtitle']??null;
			if ($subtitle) {
				$row_html .= "<div class=row><div class=col-12><div class=\"subtitle mb-3 alert alert-warning\">$subtitle</div></div></div>";
			}
			if ($layout_row['content'] === true) {
				$layout_row['content'] = array_diff(array_keys($this->widgets), $this->widgets_used);
			}
			foreach ($layout_row['content'] as $widget_name ) {
				$fs = '';
				$open_divs = 0;
				if ($widget = $this->widgets[$widget_name]??false) {
					$this->widgets_used[] = $widget_name;
					if ($widget instanceof \yii\bootstrap5\ActiveField) {
						// bs5 ActiveFields add a row container over the whole field
						if ($widget->horizontalCssClasses['layout']??false) {
							$widget_layout = ArrayHelper::remove($widget->horizontalCssClasses, 'layout');
						} else {
							$widget_layout = $widget->layout??'large';
						}
						if ($layout_row['size'] == 'small' || $cols >= 4) {
							switch ($widget_layout) {
								case 'short':
									$widget_layout = 'medium';
									break;
								case 'medium':
									$widget_layout = 'large';
									break;
							}
						} else if ($layout_row['size'] == 'medium' || $cols >= 3) {
							switch ($widget_layout) {
								case 'short':
									$widget_layout = 'medium';
									break;
								case 'medium':
									$widget_layout = 'large';
									break;
							}
						}
						Html::addCssClass($widget->options, "layout-$layout_row_layout");
						$col_classes = $this->columnClasses($widget_layout == 'full' ? 1 : $cols);
						$fs .=  "<div class=\"$col_classes\">";
						$open_divs++;
						if ($widget_layout != 'full') {
							Html::addCssClass($widget->options, 'w-100');
						}
						Html::addCssClass($widget->options, 'row');
						$fs .= $this->layoutActiveField($widget_name, $widget, $layout_row, $widget_layout, $layout_row_layout, $indexf++);
					} else if (is_array($widget)) { // Recordview attribute
						$widget_layout = $widget['layout']??'large';
						/// @todo refactor
						if ($layout_row['size'] == 'small') {
							switch ($widget_layout) {
								case 'short':
									$widget_layout = 'medium';
									break;
								case 'medium':
									$widget_layout = 'large';
									break;
							}
						} else if ($layout_row['size'] == 'medium') {
							switch ($widget_layout) {
								case 'short':
									$widget_layout = 'medium';
									break;
								case 'medium':
									$widget_layout = 'large';
									break;
							}
						}
						if ($layout_row_style == 'grid-cards' || $layout_row_style == 'grid') {
							// $col_classes = $this->columnClasses($widget_layout == 'full' ? 1 : $cols);
							// if ($col_classes) {
							// 	$fs .=  "<div class=\"$col_classes\">";
							// 	$open_divs++;
							// }
							// if ($widget_layout != 'full') {
							// 	if ($layout_row_style != 'grid-cards') {
									$open_divs++;
									$fs .= "<div class=\"row w-100\">";
								// }
// 								} else {
// 									$widget['label'] = false;
							// }
							$fs .= $this->layoutOneField($widget, $layout_row, $widget_layout, $indexf++);
						} else {
							$fs .= "<div class=\"row w-100\">";
							$open_divs++;
							$fs .= $this->layoutOneField($widget, $layout_row, $widget_layout, $indexf++);
						}
					} else if (is_string($widget)) {
						throw new \Exception($widget . ': invalid widget');
					} else {
						throw new \Exception(get_class($widget) . ': invalid widget class');
					}
					for ( ; $open_divs>0; $open_divs--) {
						$fs .= '</div>';
					}
					$row_html .= $fs;
				} else {
					if (YII_ENV_DEV) {
						Yii::warning("$widget_name: widget in fieldsLayout not found in form field definitions");
					}
				}
			}
			if (($title = $layout_row['title']??false) != false) {
				$legend = Html::tag('legend', $title, $layout_row['title_options']??[]);
				$ret .= Html::tag('fieldset', "<div class=row>$legend<hr/>$row_html</div>", $layout_row['htmlOptions']??[]);
			} else {
				$ret .= $row_html;
			}
			if (!$has_parent_col) {
				$ret .= '</div>';
			}
			break;

		case 'buttons':
			$ret .= '<div class="mt-2 clearfix row">';
			if ($layout_row_layout != 'inline') {
				$classes = $this->widget_layout_horiz_config[$layout_row_layout]['large']['horizontalCssClasses']['offset'];
				$ret .= '<div class="' . implode(' ', (array)$classes) . '">';
			} else {
				$ret .= "<div>";
			}
			$ret .= $this->layoutButtons($layout_row['content'], $layout_row_layout, $layout_row['htmlOptions']??[]);
			$ret .= '</div><!--buttons -->' .  "\n";
			$ret .= '</div><!--row-->';
			break;
		case 'subtitle':
			$ret .= $this->layoutSubtitle($layout_row['content'], $layout_row_layout, 'large', $layout_row['htmlOptions']??[]);
			break;
		case 'label&content':
			$ret .= $this->layoutContent($layout_row['label'], $layout_row['content'], $layout_row_layout, 'large', $layout_row['htmlOptions']??[]);
			break;
		case 'html':
			if (!$has_parent_col) {
				$ret .= "<div class=col-12>";
			}
			$label = ArrayHelper::remove($layout_row, 'label', null);
			$classes = $this->widget_layout_horiz_config[$layout_row_layout]['full']['horizontalCssClasses'];
			// if ($label) {
			// 	$labelOptions = [ 'class' => implode(' ', (array)$classes['label'])];
			// 	if (YII_ENV_DEV) {
			// 		$labelOptions['class'] .= " {$layout_row_layout}xlarge";
			// 	}
			// 	$ret .= Html::tag('label', $label, $labelOptions );
			// }
			if (YII_ENV_DEV) {
				$classes['wrapper'][] = "{$layout_row_layout}xlarge";
			}
			Html::addCssClass($layout_row['htmlOptions'], 'row w-100 html');
			$content_options = [];
			Html::addCssClass($content_options, $classes['wrapper']);
			foreach ((array)$layout_row['content'] as $html_key => $html_content) {
				$ret .= Html::tag('div',
							Html::tag('div', $html_content, $content_options),
								  $layout_row['htmlOptions']);
				$ret .= "<!--html row $html_key-->";
			}
			if (!$has_parent_col) {
				$ret .= '</div>';
			}
			break;
		}
		if (!$has_parent_row) {
			$ret .= '</div><!--parent row-->';
		}
		return $ret;
	}

	protected function layoutActiveField(string $widget_name, $widget, array $layout_row,
		string $widget_layout, string $layout_of_row, int $indexf): string
	{
		$fs = '';
		$row_style = $layout_row['style']??'grid';
		switch ($row_style) {
			case 'grid':
			case 'rows':
			case 'grid-nolabels':
				if ('static' == $widget_layout) {
					$classes = $this->widget_layout_horiz_config['static']['horizontalCssClasses'];
				} else if ('inline' == $layout_of_row) {
					$classes = $this->widget_layout_horiz_config['inline']['horizontalCssClasses'];
				} else {
					$classes = $this->widget_layout_horiz_config[$layout_of_row][$widget_layout]['horizontalCssClasses'];
					if ($row_style == 'grid-nolabels') {
						$widget->enableLabel = false;
					// } elseif ($row_style == 'grid-full-labels') {
					// 	echo "<pre>"; print_r($this->widget_layout_horiz_config); die;
					// 	$label_classes = $this->widget_layout_horiz_config['vertical']['horizontalCssClasses'];
					// 	$classes['label'] = $label_classes['label'];
					} else {
						$widget->labelOptions['class'] = implode(' ', $classes['label']) . " fld-$widget_name col-form-label";
						if (YII_ENV_DEV) {
							$widget->labelOptions['class'] .= " {$layout_of_row}x$widget_layout";
						}
					}
				}
				$widget->wrapperOptions['class'] = implode(' ', $classes['wrapper']);
				if (YII_ENV_DEV) {
					$widget->wrapperOptions['class'] .= " {$layout_of_row}x$widget_layout";
				}
				if ($this->widget_painter) {
					$fs .= call_user_func($this->widget_painter, $widget, $classes, $indexf++);
				} else {
					$fs .= $widget->__toString();
				}
				break;
			case 'grid-cards':
				$lo = ['class' => "card-header fld-$widget_name"];
				$ro = ['class' => "card border-primary my-3 w-100"];
				$fs .= '<div' . Html::renderTagAttributes($ro) . '>';
				if ($this->widget_painter) {
					$fs .= call_user_func($this->widget_painter, $widget, [
						'labelOptions' => $lo,
						'wrapperOptions' => [ 'class' => "card-text fld-$widget_name" ]],
						$indexf++);
				} else {
					$fs .= $widget->__toString();
				}
				$fs .= "</div><!--$widget_name-->";
				break;
			default:
				throw new InvalidConfigException($row_style . ": valid styles are: grid, grid-nolabels grid-cards");
		}
		return $fs;
	}

	protected function layoutOneField(array $widget, array $layout_row,
		string $widget_layout, int $indexf): string
	{
		$fs = '';
		$widget_name = $widget['attribute'];
		$layout_of_row = $layout_row['layout']??'1col';
		switch ($row_style = $layout_row['style']??'grid') {
			case 'grid':
			case 'grid-nolabels':
			case 'rows':
				if ($widget_layout == 'checkbox') {
					$widget_layout = 'large';
				}
				if ('static' == $widget_layout) {
					$classes = $this->widget_layout_horiz_config['static']['horizontalCssClasses'];
				} else if ($layout_of_row == 'inline') {
					$classes = [];
				} else {
					$classes = $this->widget_layout_horiz_config[$layout_of_row][$widget_layout]['horizontalCssClasses'];
					if ($row_style == 'grid-nolabels') {
						$classes['labelOptions'] = false;
					} else {
						$classes['labelOptions']['class'] = implode(' ', $classes['label']) . " fld-$widget_name";
						if (YII_ENV_DEV) {
							$classes['labelOptions']['class'] .= " {$layout_of_row}x$widget_layout";
						}
					}
				}
				$classes['wrapperOptions']['class'] = implode(' ', $classes['wrapper']) . ' fld-' . $widget_name;
				if (YII_ENV_DEV) {
					$classes['wrapperOptions']['class'] .= " {$layout_of_row}x$widget_layout";
				}
				$fs .= call_user_func($this->widget_painter, $widget, $classes, $indexf++);
				break;
			case 'grid-cards':
				$ro = ['class' => "card border-primary my-3 w-100"];
				$fs .= '<div' . Html::renderTagAttributes($ro) . '>';
				$lo = ['class' => "card-header fld-$widget_name"];
				if ($this->widget_painter) {
					$fs .= call_user_func($this->widget_painter, $widget, [
						'labelOptions' => $lo,
						'wrapperOptions' => [ 'class' => "card-text fld-$widget_name" ]],
						$indexf++);
				} else {
					$fs .= $widget->__toString();
				}
				$fs .= "</div><!--$widget_name-->";
				break;
			default:
				throw new InvalidConfigException($row_style . ": invalid style");
		}
		return $fs;
	}

	protected function layoutContent(string $label, string $content, string $layout_of_row,
									 string $widget_layout, array $options = []):string
	{
		$ret = '';
		$classes = $this->widget_layout_horiz_config[$layout_of_row][$widget_layout]['horizontalCssClasses'];
		$ret .= '<div class="row w-100">';
		if (!empty($label)) {
			$ret .= Html::tag('label', $label, [ 'class' => $classes['label']]);
		}
		$ret .= Html::tag('div', $content, [ 'class' => array_merge(['field'], $classes['wrapper'])]);
		$ret .= '</div>';
		return $ret;
	}

	protected function layoutSubtitle(string $content, string $layout_of_row,
		string $widget_layout, array $options = []):string
	{
		$ret = '';
		$classes = $this->widget_layout_horiz_config[$layout_of_row][$widget_layout]['horizontalCssClasses'];
		$tag = ArrayHelper::remove($options, 'tag', 'h2');
		$ret .= '<div class="row mb-3">';
		$ret .= Html::tag($tag, $content, $options);
		$ret .= '</div>';
		return $ret;
	}
	public function layoutButtons(array $buttons, string $layout, array $options = []): string
	{
		$buttons = FormHelper::displayButtons($buttons);
		Html::addCssClass($options, 'btn-group');
		return <<<html
<div class="{$options['class']}">
$buttons
</div><!--buttons-->
html;
	}


	public function columnClasses(int $cols): string
	{
		switch ($cols) {
			case 2:
				return "col-12 col-md-6";
				break;
			case 3:
				return "col-12 col-lg-6 col-xl-4";
			case 4:
				return "col-12 col-md-3";
			case 1:
			default:
				return "col-12";
		}
	}

} // form
