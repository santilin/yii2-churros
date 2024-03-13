<?php

namespace santilin\churros\widgets;
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

	public function layout($type = 'widgets', string $layout = '1col', string $style = 'row'): string
	{
		if (is_string($this->widgetsLayout)) {
			$layout = $this->widgetsLayout;
		}
		if ($layout == 'horizontal') {
			$layout = '1col';
		}
		if (empty($this->widgetsLayout) || is_string($this->widgetsLayout)) {
			$this->widgetsLayout = [
				[
					'type' => $type,
					'layout' => $layout,
					'content' => array_keys($this->widgets),
				]
			];
		}
		return $this->layoutWidgets($this->widgetsLayout, ['layout' => $layout, 'style' => $style]);
	}


	/**
	 * Recursivelly lays out the widgets
	 */
	protected function layoutWidgets(array $layout_rows, array $parent_options = []): string
	{
		$parent_layout = $parent_options['layout']??'1col';
		$parent_style = $parent_options['style']??'grid';
		$parent_type = $parent_options['type']??null;
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
			$layout_rows = [ ['type' => 'widgets', 'content' => $layout_rows, 'layout' => $parent_layout] ];
		}
		$ret = '';
		foreach ($layout_rows as $lrk => $layout_row) {
			$layout_of_row = $layout_row['layout']??$parent_layout;
			if ($layout_of_row == 'inline') {
				$cols = 10000;
			} else {
				$cols = intval($layout_of_row)?:1;
			}
			$type_of_row = $layout_row['type']??'widgets';
			switch ($type_of_row) {
			case 'container':
                switch ($layout_row['layout']??'rows') {
                    case 'tabs':
						$ret .= '<div class=row><div class="' . $this->columnClasses(1) . '">';
                        $tab_items = [];
                        foreach ($layout_row['content'] as $kc => $content) {
                            if (!is_array($content)) {
                                $content = [
                                    'label' => $kc,
                                    'content' => $content
                                ];
                            }
                            $tab_items[] = [
                                'label' => $content['title']??$kc,
                                'content' => $this->layoutWidgets($content['content'],
                                    ['layout' => $content['layout']??$layout_of_row, 'style' => $content['style']??$parent_style, 'type' => $type_of_row ]),
                            ];
                        }
                        $ret .= Tabs::widget([ 'items' => $tab_items ]);
						$ret .= '</div></div>';
                        break;
                    case 'rows':
						$cols = max(count($layout_row['content']), 4);
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
				if ($parent_layout == '3cols') {
					$layout_of_row = 'vertical';
				}
				$nf = $indexf = 0;
                $only_widget_names = true;
                foreach($layout_row as $lrk => $rl) {
                    if (is_array($rl)) {
                        $only_widget_names = false;
                        break;
                    }
                }
                if ($only_widget_names) {
					die ("imposible");
                    $layout_row = ['type' => $type_of_row, 'content' => $layout_row];
                }
                if (!isset($layout_row['style'])) {
					$layout_row['style'] = $parent_style;
				}
				$has_widgets = false;
				$must_add_row = true;
				$ret .= '<div class=row>';
                foreach ($layout_row['content'] as $widget_name ) {
					$fs = '';
					if ($widget = $this->widgets[$widget_name]??false) {
						$has_widgets = true;
						switch($type_of_row) {
						case 'widgets':
							$open_divs = 0;
							if ($widget instanceof \yii\bootstrap5\ActiveField ) {
								// bs5 ActiveFields add a row container over the whole field
								if ($widget->horizontalCssClasses['layout']??false) {
									$widget_layout = ArrayHelper::remove($widget->horizontalCssClasses, 'layout');
								} else {
									$widget_layout = $widget->layout??'large';
								}
								if ($widget_layout == 'full' && $nf != 0) {
									while (++$nf%$cols != 0);
								}
								Html::addCssClass($widget->options, "layout-$layout_of_row");
								$col_classes = $this->columnClasses($widget_layout == 'full' ? 1 : $cols);
								$fs .=  "<div class=\"$col_classes\">";
								$open_divs++;
								if ($widget_layout != 'full') {
									$fs .= "<div class=\"w-100\">"; // add here 'row' in bs4
									$open_divs++;
								}
								$fs .= $this->layoutActiveField($widget_name, $widget, $layout_row, $widget_layout, $layout_of_row, $indexf++);
								for ( ; $open_divs>0; $open_divs--) {
									$fs .= '</div>';
								}
							} else {
								throw new \Exception(get_class($widget) . ': invalid widget class');
							}
							break;
						case 'fields':
							$open_divs = 0;
							if ($parent_style == 'grid-cards') {
								$widget_layout = $widget['layout']??'medium';
								$widget_options = $widget['htmlOptions']??[];
								$col_classes = $this->columnClasses($widget_layout == 'full' ? 1 : $cols);
								if ($col_classes) {
									$fs .=  "<div class=\"$col_classes\">";
									$open_divs++;
								} else if ($widget_layout != 'full') {
									if ($parent_style != 'grid-cards') {
										$fs .= "<div class=w-100>";
										$open_divs++;
									} else {
										$open_divs++;
										$fs .= "<div class=\"row w-100\">";
									}
								}
								$fs .= $this->layoutOneField($widget_name, $widget, $layout_row, $widget_layout, $layout_of_row, $widget_options, $indexf++);
							} else {
								$widget_layout = $widget['layout']??'medium';
								$widget_options = $widget['htmlOptions']??[];
								$fs .= "<div class=\"row w-100\">";
								$open_divs++;
								$fs .= $this->layoutOneField($widget_name, $widget, $layout_row, $widget_layout, $layout_of_row, $widget_options, $indexf++);
							}
							for ( ; $open_divs>0; $open_divs--) {
								$fs .= '</div>';
							}
							break;
						default:
							throw new InvalidConfigException($type_of_row. ": invalid type of row");
						}
						$nf++;
						// if (isset($layout_row['title']) && $type_of_row == 'widgetset' ) {
						// 	$legend = Html::tag('legend', $layout_row['title'], $layout_row['title_options']??[]);
						// 	$ret .= Html::tag('widgetset', $legend . $fs, array_merge( ['id' => $this->options['id'] . "_layout_$lrk" ], $layout_row['options']??[]) );
						// } else if( isset($layout_row['title'])  ) {
						// 	$legend = Html::tag('div', $layout_row['title'], $layout_row['title_options']??[]);
						// 	$ret .= Html::tag('div', $legend . $fs, array_merge( ['id' => $this->options['id'] . "_layout_$lrk" ], $layout_row['options']??[]) );
						// } else {
						// 	$ret .= $fs;
						// }
						$ret .= $fs;
					}
				}
				$ret .= '</div><!-- main row-->';
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
				if ($parent_layout == '3cols') {
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
				} else if ($layout_of_row == 'inline') {
					$classes = [];
				} else {
					$classes = $this->widget_layout_horiz_config[$layout_of_row][$widget_layout]['horizontalCssClasses'];
					if ($row_style == 'grid-nolabels') {
						$widget->labelOptions = false;
					} else {
						$widget->labelOptions['class'] = implode(' ', $classes['label']) . " fld-$widget_name";
						if (YII_ENV_DEV) {
							$widget->labelOptions['class'] .= " {$layout_of_row}x$widget_layout";
						}
					}
				}
				$widget->wrapperOptions['class'] = implode(' ', $classes['wrapper']) . ' widget-container';
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
				throw new InvalidConfigException($row_style . ": invalid style");
		}
		return $fs;
	}

	protected function layoutOneField(string $widget_name, $widget, array $layout_row,
		string $widget_layout, string $layout_of_row, array $widget_options, int $indexf): string
	{
		$fs = '';
		$row_style = $layout_row['style'];
		switch ($row_style) {
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
				$classes['wrapperOptions']['class'] = implode(' ', $classes['wrapper']) . ' widget-container';
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
