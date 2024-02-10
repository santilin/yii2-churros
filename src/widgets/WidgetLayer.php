<?php

namespace santilin\churros\widgets;
use yii\base\InvalidConfigException;
use yii\helpers\{ArrayHelper,Html};
use yii\bootstrap5\Tabs;
use santilin\churros\helpers\FormHelper;

class WidgetLayer
{
	public $widgetsLayout;

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
		foreach($widgets_in_row as $lrk => $row_layout ) {
			$layout = $row_layout['layout']??$widgets_layout;
			switch ($row_layout['type']) {
			case 'container':
				$this->addLayoutClasses($widgets_cfg, $row_layout['content'], $layout);
				break;
			case 'widgetset':
			case 'widgets':
				$nf = 0;
				foreach ($row_layout['widgets'] as $fldname) {
					if (!isset($widgets_cfg[$fldname])) {
						$widgets_cfg[$fldname] = $this->fieldClasses($layout, 'large');
					} else {
						if (isset($widgets_cfg[$fldname]['layout'])) {
							$fld_layout = $widgets_cfg[$fldname]['layout'];
							unset($widgets_cfg[$fldname]['layout']);
						} else {
							$fld_layout = 'large';
						}
						$widgets_cfg[$fldname] = array_merge(
							$this->fieldClasses($layout,$fld_layout),
							$widgets_cfg[$fldname]);
					}
					switch($row_layout['labels']??null) {
					case 'none':
						$widgets_cfg[$fldname]['horizontalCssClasses']['label'][] = 'hidden';
						break;
					case 'vertical':
						$widgets_cfg[$fldname]['horizontalCssClasses']['label']
							= $widgets_cfg[$fldname]['horizontalCssClasses']['wrapper']
							= 'col-lg-12 col-md-12 col-sm-12 col-12 col-12';
					}
					if ($nf == 0 && !empty($row_layout['hide_first_label']) ) {
						$widgets_cfg[$fldname]['horizontalCssClasses']['label'] = "hidden";
					}
					$nf++;
				}
			}
		}
	}


	/**
	 * Recursivelly lays out the fiels of a form
	 */
	public function layoutwidgets(array $layout_rows, array $widgets, array $parent_options = []): string
	{
		$parent_layout = $parent_options['layout']??'1col';
		$parent_style = $parent_options['style']??'grid';
		$only_field_names = true;
		foreach($layout_rows as $lrk => $row_layout ) {
			if (is_array($row_layout)) {
				$only_field_names = false;
				break;
			}
		}
		if ($only_field_names) {
			$layout_rows = [ ['type' => 'widgets', 'widgets' => $layout_rows, 'layout' => $parent_layout] ];
		}
		$ret = '';
		foreach($layout_rows as $lrk => $row_layout ) {
			$layout_of_row = $row_layout['layout']??$parent_layout;
			if ($layout_of_row == 'inline' ) {
				$cols = 10000;
			} else {
				$cols = intval($layout_of_row)?:1;
			}
			$type = $row_layout['type']??'widgets';
			switch ($type) {
			case 'container':
                switch ($row_layout['style']??'row') {
                    case 'tabs':
						$ret .= '<div class=row><div class="' . $this->columnClasses(1) . '">';
                        $tab_items = [];
                        foreach ($row_layout['content'] as $kc => $content) {
                            if (!is_array($content)) {
                                $content = [
                                    'label' => $kc,
                                    'content' => $content
                                ];
                            }
                            $tab_items[] = [
                                'label' => $content['title']??$kc,
                                'content' => $this->layoutwidgets($content['widgets'], $widgets,
                                    ['layout' => $content['layout']??$layout_of_row, 'style' => $content['style']??$parent_style]),
                            ];
                        }
                        $ret .= Tabs::widget([ 'items' => $tab_items ]);
						$ret .= '</div></div>';
                        break;
                    case 'row':
						$ret .= '<div class=row>';
                        foreach ($row_layout['content'] as $kc => $content) {
                            $ret .= '<div class="' . $this->columnClasses($cols) . '">';
                            $ret .= $this->layoutwidgets([$content], $widgets,
                                ['layout' => $layout_of_row, 'style' => $parent_style]);
                            $ret .= "</div>\n";
                        }
                        $ret .= '</div>';
                        break;
                }
				$ret .= "<!--container_[$lrk]-->";
				break;
			case 'widgets':
			case 'widgetset':
                $nf = $indexf = 0;
                $fs = '';
                $only_field_names = true;
                foreach($row_layout as $lrk => $rl) {
                    if (is_array($rl)) {
                        $only_field_names = false;
                        break;
                    }
                }
                if ($only_field_names) {
                    $row_layout = ['type' => 'widgets', 'widgets' => $row_layout];
                }
                foreach ($row_layout['widgets'] as $attribute => $form_field ) {
					if (!is_array($form_field)) {
						$attribute = $form_field;
					}
					if (isset($widgets[$attribute])) {
						$fld_layout=$widgets[$attribute]->layout??'large';
                        if ($fld_layout == 'full' && $nf != 0) {
                            while ($nf++%$cols != 0);
                        }
                        if( ($nf%$cols) == 0) {
                            if( $nf != 0 ) {
                                $fs .= '</div><!--row-->';
                            }
                            $fs .= "\n" . "<div class=\"row layout-$layout_of_row\">";
                        }
                        $row_style = $row_layout['style']??$parent_style;
                        switch ($row_style) {
                            case 'grid':
                            case 'grid-nolabels':
                                $ro = ['class' => "field-container"];
                                if ('static' == ($fld_layout)) {
                                    $classes = ActiveForm::FIELD_HORIZ_CLASSES['static']['horizontalCssClasses'];
                                } else if ($layout_of_row == 'inline') {
									$classes = [];
								} else {
                                    $classes = ActiveForm::FIELD_HORIZ_CLASSES[$layout_of_row][$fld_layout]['horizontalCssClasses'];
                                }
                                // if ($row_style == 'grid-nolabels') {
                                //     $lo = false;
                                // } else {
                                //     $lo = ['class' => "label-$form_field " . implode(' ', $classes['label'])];
                                // }
                                // $co = [ 'class' => "field field-$form_field " . $classes['wrapper'] ];
                                $col_classes = $this->columnClasses($fld_layout == 'full' ? 1 : $cols);
                                if ($col_classes == 'col col-12') {
									$fs .= $widgets[$form_field];
                                } else {
                                    $fs .= "<div class=\"$col_classes\">";
									$fs .= $widgets[$form_field];
									$fs .= '</div>';
                                }
                                break;
                            case 'grid-cards':
                                $col_classes = $this->columnClasses($fld_layout == 'full' ? 1 : $cols);
                                $fs.= '<div class="col ' . $col_classes . '">';
                                $ro = ['class' => "card field-container border-primary my-3 w-100"];
                                $lo = ['class' => "card-header label-$form_field"];
                                $co = ['class' => 'card-text'];
                                $fs .= '<div' . Html::renderTagAttributes($ro) . '>';
                                $fs .= $this->renderAttribute($form_field, $lo, $co, $indexf++);
                                $fs .= "</div></div><!--$form_field-->";
                                break;
                        }
                        $nf++;
                    } else {
                        throw new InvalidConfigException($form_field . ": 'widgets' not found in row layout");
                    }
                }
                $fs .= '</div><!--row-->';
				if( isset($row_layout['title']) && $type == 'widgetset' ) {
					$legend = Html::tag('legend', $row_layout['title'], $row_layout['title_options']??[]);
					$ret .= Html::tag('widgetset', $legend . $fs, array_merge( ['id' => $this->options['id'] . "_layout_$lrk" ], $row_layout['options']??[]) );
				} else if( isset($row_layout['title'])  ) {
					$legend = Html::tag('div', $row_layout['title'], $row_layout['title_options']??[]);
					$ret .= Html::tag('div', $legend . $fs, array_merge( ['id' => $this->options['id'] . "_layout_$lrk" ], $row_layout['options']??[]) );
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
				$ret .= $this->layoutButtons($row_layout['buttons'], $layout_of_row??$this->layout, $row_layout['options']??[]);
				$ret .= '</div><!--buttons -->' .  "\n";
				$ret .= '</div><!--row-->';
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
