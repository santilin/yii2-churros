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

	public function layout($type = 'fields', string $layout = '1col', string $style = 'row'): string
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
		$only_widget_names = true;
		foreach($layout_rows as $lrk => $layout_row ) {
			if (is_array($layout_row)) {
				$only_widget_names = false;
				break;
			}
		}
		if ($only_widget_names) {
			$layout_rows = [ ['type' => 'widgets', 'content' => $layout_rows, 'layout' => $parent_layout] ];
		}
		$ret = '';
		foreach($layout_rows as $lrk => $layout_row ) {
			$layout_of_row = $layout_row['layout']??$parent_layout;
			if ($layout_of_row == 'inline' ) {
				$cols = 10000;
			} else {
				$cols = intval($layout_of_row)?:1;
			}
			$type = $layout_row['type']??'widgets';
			switch ($type) {
			case 'container':
                switch ($layout_row['style']??'row') {
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
                                    ['layout' => $content['layout']??$layout_of_row, 'style' => $content['style']??$parent_style]),
                            ];
                        }
                        $ret .= Tabs::widget([ 'items' => $tab_items ]);
						$ret .= '</div></div>';
                        break;
                    case 'row':
						$ret .= '<div class=row>';
                        foreach ($layout_row['content'] as $kc => $content) {
                            $ret .= '<div class="' . $this->columnClasses($cols) . '">';
                            $ret .= $this->layoutWidgets([$content],
                                ['layout' => $layout_of_row, 'style' => $parent_style]);
                            $ret .= "</div>\n";
                        }
                        $ret .= '</div>';
                        break;
                }
				$ret .= "<!--container_[$lrk]-->";
				break;
			case 'widgets':
			case 'fields':
			case 'fieldset':
				$nf = $indexf = 0;
                $fs = '';
                $only_widget_names = true;
                foreach($layout_row as $lrk => $rl) {
                    if (is_array($rl)) {
                        $only_widget_names = false;
                        break;
                    }
                }
                if ($only_widget_names) {
                    $layout_row = ['type' => $type, 'content' => $layout_row];
                }
                foreach ($layout_row['content'] as $widget_name => $widget ) {
					if (!is_array($widget)) {
						$widget_name = $widget;
					} else {
						throw new \Exception('e');
					}
					if ($widget = $this->widgets[$widget_name]??false) {
						// forms
						if ($widget instanceof \yii\bootstrap4\ActiveField ||
							$widget instanceof \yii\bootstrap5\ActiveField ) {
							if ($widget->horizontalCssClasses['layout']??false) {
								$widget_layout = ArrayHelper::remove($widget->horizontalCssClasses, 'layout');
							} else {
								$widget_layout = $widget->layout??'large';
							}
							if ($widget_layout == 'full' && $nf != 0) {
								while ($nf++%$cols != 0);
							}
 							if( ($nf%$cols) == 0) {
 								if( $nf != 0 ) {
 									$fs .= "</div><!--row-->\n";
 								}
 								Html::addCssClass($widget->options, "layout-$layout_of_row");
 								$fs .= '<div class=row>';
							}
							$col_classes = $this->columnClasses($widget_layout == 'full' ? 1 : $cols);
						} else {
							$widget_layout = $widget['layout']??'large';
							$widget_options = $widget['htmlOptions']??['class' => 'row'];
							$col_classes = $this->columnClasses($widget_layout == 'full' ? 1 : $cols);
 							if ($col_classes == 'col-12') {
 								$col_classes = null;
 							}
							if ($widget_layout == 'full' && $nf != 0) {
								while ($nf++%$cols != 0);
							}
							if( ($nf%$cols) == 0) {
								if( $nf != 0 ) {
									$fs .= "</div><!--row-->\n";
								}
								Html::addCssClass($widget_options, "layout-$layout_of_row");
								$fs .= '<div ' . Html::renderTagAttributes($widget_options) . '>';
							}
						}
						if ($col_classes) {
							$fs .=  "<div class=\"$col_classes\">";
						}
                        $row_style = $layout_row['style']??$parent_style;
                        switch ($row_style) {
                            case 'grid':
                            case 'grid-nolabels':
                                if ('static' == $widget_layout) {
                                    $classes = $this->widget_layout_horiz_config['static']['horizontalCssClasses'];
                                } else if ($layout_of_row == 'inline') {
									$classes = [];
								} else {
                                    $classes = $this->widget_layout_horiz_config[$layout_of_row][$widget_layout]['horizontalCssClasses'];
                                }
                                if ($row_style == 'grid-nolabels') {
									$widget_options['label'] = false;
                                } else {
									$widget_options['label']['class'] = implode(' ', $classes['label']) . " fld-$widget_name";
								}
								$widget_options['wrapper']['class'] = implode(' ', $classes['wrapper']) . ' widget-container';
								$widget_options['horizontalCssClasses'] = $classes;
								if ($this->widget_painter) {
									$fs .= call_user_func($this->widget_painter, $widget, $widget_options, $indexf++);
								} else {
									$fs .= $widget->__toString();
								}
                                break;
                            case 'grid-cards':
                                $ro = ['class' => "card border-primary my-3 w-100"];
                                $lo = ['class' => "card-header fld-$widget_name"];
                                $fs .= '<div' . Html::renderTagAttributes($ro) . '>';
								if ($this->widget_painter) {
									$fs .= call_user_func($this->widget_painter, $widget, ['label' => $lo, 'wrapper' => [ 'class' => "card-text fld-$widget_name" ]], $indexf++);
								} else {
									$fs .= $widget->__toString();
								}
                                $fs .= "</div><!--$widget_name-->";
                                break;
							default:
								throw new InvalidConfigException($row_style . ": invalid style");
                        }
						if ($col_classes) {
							$fs .= '</div>';
						}
						$nf++;
                    // } else {
                    //     throw new InvalidConfigException($widget . ": 'widgets' not found in row layout");
                    }
                }
 				$fs .= '</div><!--row-->';
				if( isset($layout_row['title']) && $type == 'widgetset' ) {
					$legend = Html::tag('legend', $layout_row['title'], $layout_row['title_options']??[]);
					$ret .= Html::tag('widgetset', $legend . $fs, array_merge( ['id' => $this->options['id'] . "_layout_$lrk" ], $layout_row['options']??[]) );
				} else if( isset($layout_row['title'])  ) {
					$legend = Html::tag('div', $layout_row['title'], $layout_row['title_options']??[]);
					$ret .= Html::tag('div', $legend . $fs, array_merge( ['id' => $this->options['id'] . "_layout_$lrk" ], $layout_row['options']??[]) );
				} else {
					$ret .= $fs;
				}
				break;
			case 'buttons':
				$ret .= '<div class="mt-2 clearfix row">';
				if ($layout_of_row != 'inline') {
					$classes = $this->widget_layout_horiz_config[$layout_of_row??'1col']['large']['horizontalCssClasses']['offset'];
					if (is_array($classes)) {
						$s_classes = implode(' ', $classes);
						$ret .= "<div class=\"$s_classes\">";
					} else {
						$ret .= "<div class=\"$classes\">";
					}
				} else {
					$ret .= "<div>";
				}
				$ret .= $this->layoutButtons($layout_row['content'], $layout_of_row??$this->layout, $layout_row['options']??[]);
				$ret .= '</div><!--buttons -->' .  "\n";
				$ret .= '</div><!--row-->';
				break;
			case 'subtitle':
				$ret .= $this->layoutContent(null, $layout_row['title'], $layout_row['options']??[]);
				break;
			}
		}
		return $ret;
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
			case 1:
				return "col-12";
			case 2:
				return "col-12 col-md-6";
				break;
			case 3:
				return "col-4";
			case 4:
			default:
				return "col-3";
		}
	}

} // form
