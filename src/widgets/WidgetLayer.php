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
		protected $widget_painter)
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
					'fields' => array_keys($this->widgets),
				]
			];
		}
		return $this->layoutWidgets($this->widgetsLayout, [
			'layout' => $layout == 'horizontal' ? '1col' : $layout,
			'style' => $style,
		]);
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
			$layout_rows = [ ['type' => 'widgets', 'widgets' => $layout_rows, 'layout' => $parent_layout] ];
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
                                'content' => $this->layoutWidgets($content[$type]??$content['widgets']??$content['fields'],
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
                    $layout_row = ['type' => $type, $type => $layout_row];
                }
                foreach ($layout_row[$type] as $widget_name => $widget ) {
					if (!is_array($widget)) {
						$widget_name = $widget;
					}
					if (isset($this->widgets[$widget_name])) {
						$widget_layout=$this->widgets[$widget_name]->layout??'large';
						$col_classes = $this->columnClasses($widget_layout == 'full' ? 1 : $cols);
                        if ($widget_layout == 'full' && $nf != 0) {
                            while ($nf++%$cols != 0);
                        }
                        if( ($nf%$cols) == 0) {
                            if( $nf != 0 ) {
                                $fs .= "</div><!--row-->\n";
                            }
                            $fs .= "<div class=\"row layout-$layout_of_row\">";
                        }
                        if ($col_classes != 'col col-12') {
							$fs .=  "<div class=\"$col_classes\">";
						}
                        $row_style = $layout_row['style']??$parent_style;
                        switch ($row_style) {
                            case 'grid':
                            case 'grid-nolabels':
                                if ('static' == ($widget_layout)) {
                                    $classes = ActiveForm::FIELD_HORIZ_CLASSES['static']['horizontalCssClasses'];
                                } else if ($layout_of_row == 'inline') {
									$classes = [];
								} else {
                                    $classes = ActiveForm::FIELD_HORIZ_CLASSES[$layout_of_row][$widget_layout]['horizontalCssClasses'];
                                }
                                if ($row_style == 'grid-nolabels') {
									$lo = false;
                                } else {
									$lo = ['class' => "label-$widget " . implode(' ', $classes['label'])];
								}
								$wo = [ 'class' => 'widget-container ' . $classes['wrapper'] ];
								$fs .= call_user_func($this->widget_painter, $widget, $lo, $wo, $indexf++);
                                break;
                            case 'grid-cards':
                                $col_classes = $this->columnClasses($widget_layout == 'full' ? 1 : $cols);
                                $fs.= '<div class="col ' . $col_classes . '">';
                                $ro = ['class' => "card field-container border-primary my-3 w-100"];
                                $lo = ['class' => "card-header label-$widget"];
                                $co = ['class' => 'card-text'];
                                $fs .= '<div' . Html::renderTagAttributes($ro) . '>';
                                $fs .= $this->renderAttribute($widget, $lo, $co, $indexf++);
                                $fs .= "</div></div><!--$widget-->";
                                break;
                        }
                        if ($col_classes != 'col col-12') {
							$fs .= '</div>';
						}
						$nf++;
                    } else {
                        throw new InvalidConfigException($widget . ": 'widgets' not found in row layout");
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
					$classes = static::FIELD_HORIZ_CLASSES[$layout_of_row??'1col']['large']['horizontalCssClasses']['offset'];
					if (is_array($classes)) {
						$s_classes = implode(' ', $classes);
						$ret .= "<div class=\"$s_classes\">";
					}
				} else {
					$ret .= "<div>";
				}
				$ret .= $this->layoutButtons($layout_row['buttons'], $layout_of_row??$this->layout, $layout_row['options']??[]);
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

	protected function columnClasses(int $cols): string
	{
		switch ($cols) {
			case 1:
				return "col col-12";
			case 2:
				return "col col-12 col-md-6";
				break;
			case 3:
				return "col col-4";
			case 4:
			default:
				return "col col-3";
		}
	}

	public function fixWidgetsLayout(array &$widgets_cfg, array $render_widgets, array $buttons = []): void
	{
		$form_layout = $this->layout;
		switch ($form_layout) {
			case 'inline':
				break;
			case 'horizontal':
				if (empty($this->widgetsLayout)) {
					$form_layout = '1col';
				} else if (is_string($this->widgetsLayout)) {
					$form_layout = $this->widgetsLayout;
					$this->widgetsLayout = null;
				}
				break;
			default:
				$this->layout = 'horizontal';
				break;
		}
		if (empty($this->widgetsLayout)) {
			$this->widgetsLayout = [];
			$this->widgetsLayout[] = [
				'type' => 'widgets',
				'widgets' => $render_widgets,
				'layout' => $form_layout,
			];
			$this->widgetsLayout[] = [
				'type' => 'buttons',
				'buttons' => $buttons,
				'layout' => $form_layout,
			];
			if ($this->layout != 'inline') {
				$this->addLayoutClasses($widgets_cfg, $this->widgetsLayout);
			}
		} else {
			$this->addLayoutClasses($widgets_cfg, $this->widgetsLayout);
		}
		// check there are no render_widgets with incorrect settings
		foreach ($widgets_cfg as $kf => $fldcfg_info) {
			if (isset($widgets_cfg[$kf]['layout'])) {
				unset($widgets_cfg[$kf]['layout']);
			}
		}
	}

	private function addLayoutClasses(array &$widgets_cfg, array $widgets_in_row, string $widgets_layout = '1col'): void
	{
		$ret = '';
		foreach($widgets_in_row as $lrk => $layout_row ) {
			$layout = $layout_row['layout']??$widgets_layout;
			switch ($layout_row['type']) {
			case 'container':
				$this->addLayoutClasses($widgets_cfg, $layout_row['content'], $layout);
				break;
			case 'widgetset':
			case 'widgets':
				$nf = 0;
				foreach ($layout_row['widgets'] as $fldname) {
					if (!isset($widgets_cfg[$fldname])) {
						$widgets_cfg[$fldname] = $this->fieldClasses($layout, 'large');
					} else {
						if (isset($widgets_cfg[$fldname]['layout'])) {
							$widget_layout = $widgets_cfg[$fldname]['layout'];
							unset($widgets_cfg[$fldname]['layout']);
						} else {
							$widget_layout = 'large';
						}
						$widgets_cfg[$fldname] = array_merge(
							$this->fieldClasses($layout,$widget_layout),
							$widgets_cfg[$fldname]);
					}
					switch($layout_row['labels']??null) {
					case 'none':
						$widgets_cfg[$fldname]['horizontalCssClasses']['label'][] = 'hidden';
						break;
					case 'vertical':
						$widgets_cfg[$fldname]['horizontalCssClasses']['label']
							= $widgets_cfg[$fldname]['horizontalCssClasses']['wrapper']
							= 'col-lg-12 col-md-12 col-sm-12 col-12 col-12';
					}
					if ($nf == 0 && !empty($layout_row['hide_first_label']) ) {
						$widgets_cfg[$fldname]['horizontalCssClasses']['label'] = "hidden";
					}
					$nf++;
				}
			}
		}
	}

	public function layoutContent(?string $label, string $content, array $options = []):string
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

	public function getLayoutClasses($field_layout, $layout_row)
	{
		if( $field_layout == 'static' ) {
			return self::FIELD_HORIZ_CLASSES['static'];
		} else {
			return self::FIELD_HORIZ_CLASSES[$field_layout][$layout_row];
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
