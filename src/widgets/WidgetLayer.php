<?php

namespace santilin\churros\widgets;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\{ArrayHelper,Html};
use yii\bootstrap5\Tabs;
use santilin\churros\helpers\FormHelper;

class WidgetLayer
{
	public function __construct(
		protected array|string|null $widgetsLayout,
		protected array $widgets,
		protected $widget_painter,
		protected array $widget_layout_horiz_config)
	{
	}

	public function layout(string $type, string $layout = '1col',
						   string $size = 'large', string $style = 'grid'): string
	{
		if (empty($this->widgetsLayout)) {
			$this->widgetsLayout = [
				[
					'type' => $type,
					'layout' => $layout,
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
		}/* else {
			if (empty($this->widgetsLayout['type'])) {
				$this->widgetsLayout['type'] = $type;
			} else if (empty($this->widgetsLayout['style'])) {
				$this->widgetsLayout['style'] = $style;
			} else if (empty($this->widgetsLayout['layout'])) {
				$this->widgetsLayout['layout'] = $layout;
			}
		}*/
		if (YII_ENV_DEV) {
			global $widgets_used;
			$widgets_used = [];
		}
		$ret = $this->layoutWidgets($this->widgetsLayout, ['size' => $size, 'style' => $style]);
		if (YII_ENV_DEV) {
			$not_used = array_diff(array_keys($this->widgets), $widgets_used);
			if (!empty($not_used)) {
				Yii::error("Widgets not used: " . json_encode($not_used));
			}
			unset($widgets_used);
		}
		return $ret;
	}


	/**
	 * Recursivelly lays out the widgets
	 */
	protected function layoutWidgets(array $layout_rows, array $parent_options = []): string
	{
		$parent_size = $parent_options['size']??'large';
		$parent_style = $parent_options['style']??'grid';
		$only_widget_names = true;
		foreach($layout_rows as $lrk => $layout_row ) {
			if (empty($layout_row)) {
				unset($layout_rows[$lrk]);
				continue;
			}
			if (is_array($layout_row)) {
				$only_widget_names = false;
				break;
			}
		}
		if ($only_widget_names) {
			$layout_rows = [
				[
					'type' => 'widgets',
					'content' => $layout_rows,
					'size' => $parent_size,
					'layout' => $parent_options['layout']??'1col',
					'style' => $parent_options['style']??'rows',
				] ];
		}
		$ret = '';
		foreach ($layout_rows as $lrk => $layout_row) {
			$layout_of_row = $layout_row['layout']??'1col';
			if ($layout_of_row == 'inline') {
				$cols = 10000;
			} else {
				$cols = intval($layout_of_row); // ?:max(count($layout_row['content']), 4);
			}
			$type_of_row = $layout_row['type']??'widgets';
			switch ($type_of_row) {
			case 'container':
                switch ($layout_row['style']??'rows') {
                    case 'tabs':
						$ret .= '<div class=row><div class="' . $this->columnClasses(1) . '">';
                        $tab_items = [];
						$has_active = false;
                        foreach ($layout_row['content'] as $kc => $content) {
							if ($content === null) {
								continue;
							}
                            if (!is_array($content)) {
                                $content = [
                                    'label' => $kc,
                                    'content' => $content
                                ];
                            }
                            if ($content['active']??false == true) {
								$has_active = true;
							}
                            $tab_items[] = [
                                'label' => $content['title']??$kc,
								'options' => $content['htmlOptions']??[],
								'active' => $content['active']??false,
								'headerOptions' => $content['headerOptions']??[],
								'content' => $this->layoutWidgets($content['content'], [
									'layout' => $content['layout']??$layout_of_row,
									'style' => $content['style']??$parent_style,
									'type' => $type_of_row ]),
                            ];
                        }
                        if (!$has_active && count($tab_items)) {
							$tab_items[0]['active'] = true;
						}
                        $ret .= Tabs::widget(['items' => $tab_items, 'tabContentOptions' => [ 'class' => 'mt-2'	] ]);
						$ret .= '</div></div>';
                        break;
                    case 'rows':
						$ret .= "<div class=\"row $cols-cols-layout\">";
                        foreach ($layout_row['content'] as $kc => $content) {
                            $ret .= '<div class="' . $this->columnClasses($cols) . '">';
                            $ret .= $this->layoutWidgets([$content],
                                ['layout' => $layout_of_row, 'style' => $content['style']??$parent_style, 'type' => $type_of_row ]);
                            $ret .= "</div>\n";
                        }
                        $ret .= '</div>';
                        break;
                    case 'cols':
						$cols = min(count($layout_row['content']), 4);
						$ret .= "<div class=\"row $cols-cols-layout\">";
                        foreach ($layout_row['content'] as $kc => $content) {
                            $ret .= '<div class="' . $this->columnClasses($cols) . '">';
                            $ret .= $this->layoutWidgets([$content],
                                ['layout' => "{$cols}cols", 'style' => $content['style']??$parent_style, 'type' => $type_of_row ]);
                            $ret .= "</div>\n";
                        }
                        $ret .= '</div>';
                        break;
                }
				$ret .= "<!--container_[$lrk]-->";
				break;
			case 'widgets':
			case 'fields':
				$indexf = 0;
                $only_widget_names = true;
                foreach($layout_row as $lrk => $rl) {
                    if (is_array($rl)) {
                        $only_widget_names = false;
                        break;
                    }
                }
                if ($only_widget_names) {
                    $layout_row = ['type' => $type_of_row, 'content' => $layout_row];
                }
                if (!isset($layout_row['style'])) {
					$layout_row['style'] = $parent_style;
				}
				$subtitle = $layout_row['subtitle']??null;
				$row_html = '';
				if ($subtitle) {
					$row_html .= "<div class=row><div class=col12><div class=\"subtitle mb-3 alert alert-warning\">$subtitle</div></div></div>";
				}
				$row_html .= '<div class=row>';
                foreach ($layout_row['content'] as $widget_name ) {
					$fs = '';
					$open_divs = 0;
					if ($widget = $this->widgets[$widget_name]??false) {
						if (YII_ENV_DEV) {
							global $widgets_used;
							$widgets_used[] = $widget_name;
						}
						if ($widget instanceof \yii\bootstrap5\ActiveField ) {
							// bs5 ActiveFields add a row container over the whole field
							if ($widget->horizontalCssClasses['layout']??false) {
								$widget_layout = ArrayHelper::remove($widget->horizontalCssClasses, 'layout');
							} else {
								$widget_layout = $widget->layout??'large';
							}
							if ($parent_size == 'small') {
								switch ($widget_layout) {
									case 'short':
										$widget_layout = 'medium';
										break;
									case 'medium':
										$widget_layout = 'large';
										break;
								}
							} else if ($parent_size == 'medium') {
								switch ($widget_layout) {
									case 'short':
										$widget_layout = 'medium';
										break;
									case 'medium':
										$widget_layout = 'large';
										break;
								}
							}
							Html::addCssClass($widget->options, "layout-$layout_of_row");
							$col_classes = $this->columnClasses($widget_layout == 'full' ? 1 : $cols);
							$fs .=  "<div class=\"$col_classes\">";
							$open_divs++;
							if ($widget_layout != 'full') {
								Html::addCssClass($widget->options, 'w-100');
							}
							Html::addCssClass($widget->options, 'row');
							$fs .= $this->layoutActiveField($widget_name, $widget, $layout_row, $widget_layout, $layout_of_row, $indexf++);
						} else if (is_array($widget)) { // Recordview attribute
							$widget_layout = $widget['layout']??'large';
							/// @todo refactor
							if ($parent_size == 'small') {
								switch ($widget_layout) {
									case 'short':
										$widget_layout = 'medium';
										break;
									case 'medium':
										$widget_layout = 'large';
										break;
								}
							} else if ($parent_size == 'medium') {
								switch ($widget_layout) {
									case 'short':
										$widget_layout = 'medium';
										break;
									case 'medium':
										$widget_layout = 'large';
										break;
								}
							}
							if ($parent_style == 'grid-cards' || $parent_style == 'grid') {
								$col_classes = $this->columnClasses($widget_layout == 'full' ? 1 : $cols);
								if ($col_classes) {
									$fs .=  "<div class=\"$col_classes\">";
									$open_divs++;
								}
								if ($widget_layout != 'full') {
									if ($parent_style != 'grid-cards') {
										$open_divs++;
										$fs .= "<div class=\"row w-100\">";
									}
// 								} else {
// 									$widget['label'] = false;
								}
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
							Yii::error("$widget_name: widget WidgetLayer not found in form field definitions");
						}
					}

				}
				$row_html .= '</div><!-- main row-->';
				if (($title = $layout_row['title']??false) != false) {
					$legend = Html::tag('legend', $title, $layout_row['title_options']??[]);
					$ret .= Html::tag('fieldset', "$legend<hr/>$row_html", $layout_row['htmlOptions']??[]);
				} else {
					$ret .= $row_html;
				}
				break;

			case 'buttons':
				$ret .= '<div class="mt-2 clearfix row">';
				if ($layout_of_row != 'inline') {
					$classes = $this->widget_layout_horiz_config[$layout_of_row]['large']['horizontalCssClasses']['offset'];
					$ret .= '<div class="' . implode(' ', (array)$classes) . '">';
				} else {
					$ret .= "<div>";
				}
				$ret .= $this->layoutButtons($layout_row['content'], $layout_of_row, $layout_row['options']??[]);
				$ret .= '</div><!--buttons -->' .  "\n";
				$ret .= '</div><!--row-->';
				break;
			case 'subtitle':
				$ret .= $this->layoutContent(null, $layout_row['title'], $layout_row['options']??[]);
				break;
			case 'html':
				$label = ArrayHelper::remove($layout_row, 'label', null);
				if ($parent_size == '3cols') {
					$layout_of_row = '1col';
				}
				$classes = $this->widget_layout_horiz_config[$layout_of_row]['large']['horizontalCssClasses'];
				if ($label) {
					$labelOptions = [ 'class' => implode(' ', (array)$classes['label'])];
					if (YII_ENV_DEV) {
						$labelOptions['class'] .= " {$layout_of_row}xlarge";
					}
					$ret .= Html::tag('label', $label, $labelOptions );
				}
				if (YII_ENV_DEV) {
					$classes['wrapper'][] = "{$layout_of_row}xlarge";
				}
				$ret .= Html::tag('div', $layout_row['content'], $classes['wrapper']);
				break;
			}
		}
		return $ret;
	}

	protected function layoutActiveField(string $widget_name, $widget, array $layout_row,
		string $widget_layout, string $layout_of_row, int $indexf): string
	{
		$fs = '';
		$row_style = $layout_row['style'];
		switch ($row_style) {
			case 'grid':
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
		switch ($row_style = $layout_row['style']) {
			case 'grid':
			case 'grid-nolabels':
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
				$classes['field'] = ['love'];
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

	protected function layoutContent(?string $label, string $content, array $options = []):string
	{
		$ret = '';
		$wrapper_options = [ 'class' => $this->fieldConfig['horizontalCssClasses']['wrapper'] ];
		if( isset($options['class']) ) {
			Html::addCssClass($wrapper_options, $options['class']);
		}
		if( empty($label) ) {
			Html::addCssClass($wrapper_options, $this->fieldConfig['horizontalCssClasses']['offset']);
		}
		$wrapper_tag = ArrayHelper::remove($wrapper_options, 'tag', 'div');
		$ret .= Html::beginTag($wrapper_tag, $wrapper_options);
		$ret .= $content;
		$ret .= Html::endTag($wrapper_tag);
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
