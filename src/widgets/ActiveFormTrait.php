<?php

namespace santilin\churros\widgets;
use yii\base\InvalidConfigException;
use yii\helpers\{ArrayHelper,Html};
use yii\bootstrap4\Tabs;
use santilin\churros\helpers\FormHelper;

trait ActiveFormTrait
{
	public $fieldsLayout;

	public function getInputId($model, string $attribute): string
	{
		return Html::getInputId($model, $attribute);
	}

	public function fixFieldsLayout(array &$fields_cfg, array $render_fields, array $buttons = []): void
	{
		$form_layout = $this->layout;
		switch ($form_layout) {
			case 'inline':
				break;
			case 'horizontal':
				if (is_string($this->fieldsLayout)) {
					$form_layout = $this->fieldsLayout;
					$this->fieldsLayout = null;
				}
				break;
			default:
				$this->layout = 'horizontal';
				break;
		}
		if (empty($this->fieldsLayout)) {
			$this->fieldsLayout = [];
			$this->fieldsLayout[] = [
				'type' => 'fields',
				'fields' => $render_fields,
				'layout' => $form_layout,
			];
			$this->fieldsLayout[] = [
				'type' => 'buttons',
				'buttons' => $buttons,
				'layout' => $form_layout,
			];
			if ($this->layout != 'inline') {
				$this->addLayoutClasses($fields_cfg, $this->fieldsLayout);
			}
		} else {
			$this->addLayoutClasses($fields_cfg, $this->fieldsLayout);
		}
		// check there are no render_fields with incorrect settings
		foreach ($fields_cfg as $kf => $fldcfg_info) {
			if (isset($fields_cfg[$kf]['layout'])) {
				unset($fields_cfg[$kf]['layout']);
			}
		}
	}

	private function addLayoutClasses(array &$fields_cfg, array $fields_in_row, string $fields_layout = '1col'): void
	{
		$ret = '';
		foreach($fields_in_row as $lrk => $row_layout ) {
			$layout = $row_layout['layout']??$fields_layout;
			switch ($row_layout['type']) {
			case 'container':
				$this->addLayoutClasses($fields_cfg, $row_layout['content'], $layout);
				break;
			case 'fieldset':
			case 'fields':
				$nf = 0;
				foreach ($row_layout['fields'] as $fldname) {
					if (!isset($fields_cfg[$fldname])) {
						$fields_cfg[$fldname] = $this->fieldClasses($layout, 'large');
					} else {
						if (isset($fields_cfg[$fldname]['layout'])) {
							$fld_layout = $fields_cfg[$fldname]['layout'];
							unset($fields_cfg[$fldname]['layout']);
						} else {
							$fld_layout = 'large';
						}
						$fields_cfg[$fldname] = array_merge(
							$this->fieldClasses($layout,$fld_layout),
							$fields_cfg[$fldname]);
					}
					switch($row_layout['labels']??null) {
					case 'none':
						$fields_cfg[$fldname]['horizontalCssClasses']['label'][] = 'hidden';
						break;
					case 'vertical':
						$fields_cfg[$fldname]['horizontalCssClasses']['label']
							= $fields_cfg[$fldname]['horizontalCssClasses']['wrapper']
							= 'col-lg-12 col-md-12 col-sm-12 col-12 col-12';
					}
					if ($nf == 0 && !empty($row_layout['hide_first_label']) ) {
						$fields_cfg[$fldname]['horizontalCssClasses']['label'] = "hidden";
					}
					$nf++;
				}
			}
		}
	}


	/**
	 * Recursivelly lays out the fiels of a form
	 */
	public function layoutFields(array $layout_rows, array $form_fields, array $parent_options = []): string
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
			$layout_rows = [ ['type' => 'fields', 'fields' => $layout_rows, 'layout' => $parent_layout] ];
		}
		$ret = '';
		foreach($layout_rows as $lrk => $row_layout ) {
			$layout_of_row = $row_layout['layout']??$parent_layout;
			if ($layout_of_row == 'inline' ) {
				$cols = 10000;
			} else {
				$cols = intval($layout_of_row)?:1;
			}
			$type = $row_layout['type']??'fields';
			switch ($type) {
			case 'container':
				$ret .= '<div class=row><div class="' . $this->columnClasses(1) . '">';
                switch ($row_layout['style']??'row') {
                    case 'tabs':
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
                                'content' => $this->layoutFields($content['fields'], $form_fields,
                                    ['layout' => $content['layout']??$layout_of_row, 'style' => $content['style']??$parent_style]),
                            ];
                        }
                        $ret .= Tabs::widget([ 'items' => $tab_items ]);
                        break;
                    case 'row':
                        foreach ($row_layout['content'] as $kc => $content) {
                            $ret .= '<div class="' . $this->columnClasses($cols) . '">';
                            $ret .= $this->layoutFields([$content], $form_fields,
                                ['layout' => $layout_of_row, 'style' => $parent_style]);
                            $ret .= "</div>\n";
                        }
                        break;
                }
				$ret .= "</div></div><!--container_[$lrk]-->";
				break;
			case 'fields':
			case 'fieldset':
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
                    $row_layout = ['type' => 'fields', 'fields' => $row_layout];
                }
                foreach ($row_layout['fields'] as $attribute => $form_field ) {
					if (!is_array($form_field)) {
						$attribute = $form_field;
					}
					if (isset($form_fields[$attribute])) {
						$fld_layout=$form_fields[$attribute]->layout??'large';
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
									$fs .= $form_fields[$form_field];
                                } else {
                                    $fs .= "<div class=\"$col_classes\">";
									$fs .= $form_fields[$form_field];
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
                        throw new InvalidConfigException($form_field . ": 'fields' not found in row layout");
                    }
                }
                $fs .= '</div><!--row-->';
				if( isset($row_layout['title']) && $type == 'fieldset' ) {
					$legend = Html::tag('legend', $row_layout['title'], $row_layout['title_options']??[]);
					$ret .= Html::tag('fieldset', $legend . $fs, array_merge( ['id' => $this->options['id'] . "_layout_$lrk" ], $row_layout['options']??[]) );
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
