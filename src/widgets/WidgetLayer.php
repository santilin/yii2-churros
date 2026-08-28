<?php

namespace santilin\churros\widgets;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\{ArrayHelper,Html, Url};
use yii\bootstrap5\Tabs;
use santilin\churros\helpers\FormHelper;
use santilin\churros\widgets\ActiveForm;

class WidgetLayer
{
	protected array $widgets_used = [];
	protected array $row_col = [];

	public function __construct(
		protected array|string|null $widgetsLayout,
		protected array $widgets,
		protected $widget_painter,
		protected array $widget_layout_horiz_config)
	{
	}

	public static function renderLayout(array $content, string $layout = '1col',
										string $size = 'large', string $style = 'grid'): string
	{
		$layer = new WidgetLayer($content, [], null, ActiveForm::FORM_FIELD_HORIZ_CLASSES);
		$ret = $layer->layoutWidgets($content, [
			'size' => $size,
			'style' => $style,
			'layout' => $layout,
		]);
		return $ret;
	}

	public function layout(string $type, string $form_layout = '1col',
						   string $size = 'large', string $style = 'grid',
						   array $html_options = []): string
	{
		if (empty($this->widgetsLayout)) {
			$this->widgetsLayout = [
				[
					'type' => $type,
					'layout' => $form_layout??'1col',
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
			'layout' => $form_layout??'1col',
			], null, $html_options);
		$not_used = array_diff(array_keys($this->widgets), $this->widgets_used);
		if (!empty($not_used)) {
			Yii::warning("Widgets in form not used in layout: '" . implode("','",$not_used) . "'");
		}
		$this->widgets_used = [];
		return $ret;
	}

	/**
	 * Recursivelly lays out the widgets
	 */
	protected function layoutWidgets(array $layout_row, array $parentOptions = [],
		int|string|null $rowKey = null, array $htmlOptions = []): string
	{
		$ret = '';
		// firstly, check if this is an array of containers (missing content) and
		// convert it to a proper container
		if (!array_key_exists('content', $layout_row)) {
			if (ArrayHelper::isIndexed($layout_row)) {
				$first_layout_row = reset($layout_row);
				if (!is_array($first_layout_row)) {
					$layout_row = [
						'type' => 'fields',
						'content' => $layout_row,
						'layout' => $parentOptions['layout']??'1col',
						'size' => $parentOptions['size']??'large',
						'style' => 'grid'
					];
				} else {
					foreach (array_filter($layout_row) as $klr => $lr) {
						$ret .= $this->layoutWidgets($lr, $parentOptions, $klr, $htmlOptions);
					}
					return $ret;
				}
			} else if (count($layout_row) == 1) {
				$ret .= '<!--' . array_key_first($layout_row) . "-->\n";
				$layout_row = reset($layout_row);
			} else {
				$layout_row = [
					'type' => 'rows',
					'content' => $layout_row,
					'layout' => '1col',
					'size' => $parentOptions['size'] ?? 'large',
					'style' => 'rows',
				];
			}
		}
		$layout_row_type = $layout_row['type'] ?? 'fields';
		$layout_row_layout = $layout_row['layout'] ?? '1col';
		if ($layout_row_type === 'container') { // deprecated
			throw new \Exception("deprecated `container` type: use one of 'tabs', 'accordion', 'details', 'rows', 'cols', 'collapsable");
		}
		$container_styles = ['tabs', 'accordion', 'details', 'rows', 'cols', 'collapsable'];
		$is_container = in_array($layout_row_type, $container_styles);
		if ($is_container && empty($layout_row['style'])) {
			$layout_row_style = $layout_row_type;
		} else {
			$layout_row_style = empty($layout_row['style'])
				? (str_contains($layout_row_layout, 'col') ? 'cols' : 'rows')
				: $layout_row['style'];
		}
		$layout_row['size'] ??= $parentOptions['size'] ?? 'large';
		if ($layout_row_layout === 'inline') {
			$cols = 10000;
		} else {
			$cols = intval($layout_row_layout);
		}
		if ($is_container) {
			$ret .= "<!--container $layout_row_style ($layout_row_type): $rowKey-->";
			$row_added = false;
			if (!$this->lastWasRow()) {
				$ret .= "<div class=\"row lay-$cols-cols lay-{$this->lastLevel()}-lvl\">";
				$this->setLastRow($cols);
				$row_added = true;
			}
			switch ($layout_row_style) {
				case 'tabs':
					$col_added = false;
					if (!$this->lastWasCol()) {
						$this->setLastCol($cols);
						$col_added = true;
						$ret .= '<div class="' . $this->columnClasses($cols) . '">';
					}
					$tab_items = [];
					$has_active = false;
					foreach ($layout_row['content'] as $kc => $tab_content) {
						if ($tab_content === null) {
							continue;
						}
						if (!is_array($tab_content)) {
							$tab_content = [
								'title' => $kc,
								'content' => $tab_content
							];
						}
						if (($tab_content['active']??false) == true) {
							$has_active = true;
						}
						$tab_items[] = [
							'label' => ArrayHelper::remove($tab_content, 'title', $kc),
							'active' => ArrayHelper::remove($tab_content, 'active', false),
							'headerOptions' => ArrayHelper::remove($tab_content, 'headerOptions', []),
							'content' => '<div class="row">' . $this->layoutWidgets($tab_content, [
								'layout' => $layout_row_layout,
								'style' => $layout_row_style,
								'type' => $layout_row_type,
							], $kc) . '</div>',
						];
					}
					if (!$has_active && count($tab_items)) {
						$tab_items[0]['active'] = true;
					}
					$tabs = new Tabs(['items' => $tab_items, 'tabContentOptions' => $layout_row['htmlOptions']??[]]);
					$tabs_id = $tabs->getId();
					$ret .= $tabs->run();
					$ret .= <<<js
<script>
document.addEventListener('DOMContentLoaded', function() {
	window.yii.churros.persistBootstrapTabs('#$tabs_id a[data-bs-toggle="tab"]');
});
</script>
js;
					if ($col_added) {
						$this->removeLast();
						$ret .= "</div>";
					}
					break;
				case 'accordion':
					$col_added = false;
					if (!$this->lastWasCol()) {
						$this->setLastCol($cols);
						$col_added = true;
						$ret .= '<div class="' . $this->columnClasses($cols) . '">';
					}
					$accordion_items = [];
					foreach ($layout_row['content'] as $kc => $row_content) {
						if ($row_content === null) continue;
						if (!is_array($row_content)) {
							$row_content = [
								'label' => $kc,
								'content' => $row_content
							];
						}
						$accordion_items[] = [
							'label' => ArrayHelper::remove($row_content, 'title', $kc),
							'content' => $this->layoutWidgets($row_content, [
								'layout' => $layout_row_layout,
								'style' => $layout_row_style,
								'type' => $layout_row_type,
							], $kc),
							'options' => ArrayHelper::remove($row_content, 'headerOptions', []),
							'expanded' => ArrayHelper::remove($row_content, 'active', false),
						];
					}
					if ($accordion_items) {
						$accordion = new \yii\bootstrap5\Accordion([
							'items' => $accordion_items,
							'options' => $layout_row['htmlOptions'] ?? [],
						]);
						$ret .= $accordion->run();
					}
					if ($col_added) {
						$this->removeLast();
						$ret .= "</div>";
					}
					break;
				case 'details':
					$col_added = false;
					if (!$this->lastWasCol()) {
						$this->setLastCol($cols);
						$col_added = true;
						$ret .= '<div class="' . $this->columnClasses($cols) . '">';
					}
					$summary_content = ArrayHelper::getValue($layout_row, 'summary', []);
					$details_content = ArrayHelper::getValue($layout_row, 'content', []);
					$is_open = ArrayHelper::getValue($layout_row, 'open', false);
					$title = ArrayHelper::getValue($layout_row, 'title', '');
					$ret .= Html::beginTag('details', $is_open ? ['open' => 'open', 'layout-details-card' => true] : ['layout-details-card' => true]);
				if (!empty($summary_content) || !empty($title)) {
					$summary_header = '';
					if (!empty($title)) {
						$summary_header .= Html::tag('div', $title, ['class' => 'me-auto']);
					} else if (!empty($summary_content)) {
						$summary_header .= Html::tag('div', $this->layoutWidgets($summary_content, [
							'layout' => $layout_row_layout,
							'style' => $layout_row_style,
							'type' => $layout_row_type,
						], 'summary'), ['class' => 'flex-grow-1']);
					}
					$summary_header .= '<i class="fa-solid fa-chevron-down details-chevron ms-3' . ($is_open ? ' rotate-180' : '') . '"></i>';
					$summary_options = [
						'style' => 'cursor: pointer; list-style: none;',
					];
					$summary_options['onclick'] = 'this.querySelector(".details-chevron").classList.toggle("rotate-180")';
					$ret .= Html::tag('summary',
						'<div class="d-flex align-items-start w-100">' . $summary_header . '</div>',
					$summary_options);
				}
					if (!empty($details_content)) {
						$content_html = $this->layoutWidgets($details_content, [
							'layout' => $layout_row_layout,
							'style' => $layout_row_style,
							'type' => $layout_row_type,
						], 'content');
						$ret .= Html::tag('div', $content_html, ['class' => 'row details-content']);
					}
					$ret .= Html::endTag('details');
					if ($col_added) {
						$this->removeLast();
						$ret .= "</div>";
					}
					break;
				case 'rows':
					if (!$this->lastWasRow()) {
						throw "Error en anidamiento de widgets";
					}
					$col_added = false;
					if (!$this->lastWasCol()) {
						$this->setLastCol($cols);
						$col_added = true;
						$col_options = $layout_row['htmlOptions'] ?? [];
						Html::addCssClass($col_options, $this->columnClasses($cols));
						$col_options['style'] = 'display: flex; flex-direction: column';
						$ret .= Html::beginTag('div', $col_options);
					}
					$rows_content = '';
					if ($layout_row['content'] === true) {
						$layout_row['content'] = array_diff(array_keys($this->widgets), $this->widgets_used);
					}
					$layout_row_content = array_filter($layout_row['content']);
					foreach ($layout_row_content as $kc => $row_content) {
						$rows_content .= "<!--row: $kc-->";
						$rows_content .= $this->layoutWidgets((array)$row_content, [
							'layout' => $layout_row_layout,
							'style' => $layout_row_style,
							'type' => $layout_row_type,
							'size' => $layout_row['size'],
						], $kc);
					}
					$ret .= $rows_content;
					if ($col_added) {
						$this->removeLast();
						$ret .= '</div>';
					} else {
						throw "Error";
					}
					break;
				case 'cols':
					if (!$this->lastWasRow()) {
						throw "Error en anidamiento de widgets";
					}
					foreach ($layout_row['content'] as $kc => $row_content) {
						$cols = intval($row_content['layout']??$layout_row_layout);
						$row_options = $row_content['htmlOptions']??[];
						if (!empty($row_options)) {
							throw new \Exception("check row_options");
						}
						// Html::addCssClass($row_options, $this->columnClasses($cols));
						// $this->setLastCol($cols);
						$ret .= $this->layoutWidgets((array)$row_content, [
							'layout' => $row_content['layout']??$layout_row_layout,
							'style' => $layout_row_style,
							'type' => $layout_row_type, ], $kc, $row_options);
						// $this->removeLast();
					}
					break;
				case 'collapsable':
					$toggle_id = ArrayHelper::getValue($layout_row, 'toggle-id', '');
					$button_label = ArrayHelper::getValue($layout_row, 'button-label', '+');
					$button_options = ArrayHelper::getValue($layout_row, 'button-options', []);
					Html::addCssClass($button_options, ['btn', 'btn-sm', 'btn-link', 'text-secondary', 'p-0', 'me-1']);
					$button_options['data-bs-toggle'] = 'collapse';
					$button_options['aria-expanded'] = 'false';
					$button_options['title'] = $button_options['title'] ?? 'Mostrar/ocultar';
					if ($toggle_id) {
						$button_options['data-bs-target'] = '#' . $toggle_id;
					}
					$button_content = ArrayHelper::remove($layout_row, 'button-content', $button_label);
					$ret .= Html::button($button_content, $button_options);
					$layout_row_content = array_filter($layout_row['content']);
					foreach ($layout_row_content as $kc => $row_content) {
						$ret .= $this->layoutWidgets((array)$row_content, [
							'layout' => $layout_row_layout,
							'style' => $layout_row_style,
							'type' => $layout_row_type,
							'size' => $layout_row['size'],
						], $kc);
					}
					break;
				default:
					throw new \Exception($layout_row_style . ': container style not valid (type: ' . $layout_row_type . ')');
			}
			if ($row_added) {
				$ret .= "</div>";
				$this->removeLast();
			}
			$ret .= "<!--end container $layout_row_style: $rowKey-->";
		} else {
			$row_added = false;
			if ($this->noLast()) {
				if (($parentOptions['layout'] ?? $layout_row_layout) === 'inline') {
					$ret .= "<div class=\"d-flex lay-$cols-cols lay-{$this->lastLevel()}-lvl\">";
				} else {
					$row_attrs = array_merge($htmlOptions, $layout_row['htmlOptions'] ?? []);
					Html::addCssClass($row_attrs, "row d-flex lay-$cols-cols lay-{$this->lastLevel()}-lvl");
					if (!empty($layout_row['id'])) {
						throw  new \Exception("deprecated use of `id` in layour row. Use `htmlOptins`");
					}
					$ret .= Html::beginTag('div', $row_attrs);
					$this->setLastRow($cols);
					$row_added = true;
				}
			}
			$col_added = false;
			$row_html = '';
			switch ($layout_row_type) {
			case 'fields':
			case 'fieldset':
			case 'widgets':
				$indexf = 0;
				$only_widget_names = true;
				foreach($layout_row as $lrk => $rl) {
					if (is_string($lrk) || is_array($rl)) {
						$only_widget_names = false;
						break;
					}
				}
				if ($only_widget_names) {
					$layout_row = ['type' => $layout_row_type, 'content' => $layout_row, 'style' => 'rows'];
				}
				$row_html = '';
				$subtitle = $layout_row['subtitle'] ?? null;
				if ($subtitle) {
					$row_html .= "<div class=row><div class=col-12><div class=\"subtitle mb-3 alert alert-warning\">$subtitle</div></div></div>";
				}
				if ($layout_row['content'] === true) {
					$layout_row['content'] = array_diff(array_keys($this->widgets), $this->widgets_used);
				}
				$breaking_rows = $layout_row['options']['breaking-rows'] ?? [];
				foreach ($layout_row['content'] as $widget_name) {
					$fs = '';
					$open_divs = 0;
					$widget = $this->widgets[$widget_name] ?? false;
					if ($widget) {
						$this->widgets_used[] = $widget_name;
						if (in_array($widget_name, $breaking_rows, true)) {
							$fs .= '<div class="w-100"></div>';
						}
						if ($widget instanceof \yii\bootstrap5\ActiveField) {
							// bs5 ActiveFields add a row container over the whole field
							if ($widget->inputOptions['layout'] ?? false) {
								$widget_layout = ArrayHelper::remove($widget->inputOptions, 'layout');
							} else {
								$widget_layout = $widget->layout ?? 'large';
							}
							if ($layout_row['size'] === 'small'/* || ($cols >= 4 && $layout_row_layout != 'inline')*/) {
								switch ($widget_layout) {
									case 'short':
										$widget_layout = 'medium';
										break;
									case 'medium':
										$widget_layout = 'large';
										break;
								}
							} else if ($layout_row['size'] === 'medium' || ($cols >= 3 && $layout_row_layout != 'inline')) {
								switch ($widget_layout) {
									case 'short':
										$widget_layout = 'medium';
										break;
									case 'medium':
										$widget_layout = 'large';
										break;
								}
							}
							if ($layout_row_layout !== 'inline') {
								Html::addCssClass($widget->options, "layout-$layout_row_layout");
								// Handle 'fill' layout - break out of parent column to span full width
								if ($widget_layout === 'fill') {
									// Close parent column if open
									while ($this->lastWasCol() && $open_divs == 0) {
										$fs .= '</div>';
										$this->removeLast();
									}
									// Add full-width row wrapper
									$fs .= '<div class="col-12">';
									$fs .= $this->layoutActiveField($widget_name, $widget, $layout_row, 'fill', $layout_row_layout, $indexf++);
									$fs .= '</div>';
									// Track that we added and removed a row
									$this->removeLast(); // Remove row tracking if any
								} elseif ($widget_layout === 'full') {
									$col_classes = $this->columnClasses(1);
									$open_divs++;
									$fs .= "<div class=\"$col_classes\">";
									$fs .= $this->layoutActiveField($widget_name, $widget, $layout_row, $widget_layout, $layout_row_layout, $indexf++);
								} else {
									Html::addCssClass($widget->options, 'w-100');
									$col_classes = $this->columnClasses($cols);
									$open_divs++;
									$fs .= "<div class=\"$col_classes\">";
									$fs .= $this->layoutActiveField($widget_name, $widget, $layout_row, $widget_layout, $layout_row_layout, $indexf++);
								}
							} else {
								$fs .= $this->layoutActiveField($widget_name, $widget, $layout_row, $widget_layout, $layout_row_layout, $indexf++);
							}
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
							$col_classes = $this->columnClasses($widget_layout == 'full' ? 1 : $cols);
							$fs .=  "<div class=\"$col_classes\">";
							$open_divs++;
							$fs .= "<div class=\"row align-items-center\">";
							$open_divs++;
							$fs .= $this->layoutOneField($widget, $layout_row, $widget_layout, $indexf++);
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
				if ($layout_row_type === 'fieldset') {
					$fieldset_options = $layout_row['htmlOptions'] ?? [];
					// a string key in fieldsLayout names the fieldset, so views and
					// scripts can target it without hardcoding an id in htmlOptions
					if (!isset($fieldset_options['id']) && is_string($rowKey) && $rowKey !== '') {
						$fieldset_options['id'] = $rowKey;
					}
					if (($title = $layout_row['title'] ?? false) !== false) {
						$legend = Html::tag('legend', $title, $layout_row['title_options']??[]);
						$ret .= Html::tag('fieldset', "$legend<hr/><div class=row>$row_html</div>", $fieldset_options);
					} else {
						$ret .= $row_html;
					}
				} else {
					$ret .= $row_html;
				}
				break;

			case 'buttons':
				if (!empty($layout_row['content'])) {
					if (($parentOptions['layout'] ?? $layout_row_layout) !== 'inline') {
						$ret .= '<div class="mt-2 clearfix row">';
						$classes = $this->widget_layout_horiz_config[$layout_row_layout]['large']['horizontalCssClasses']['offset'];
						$ret .= '<div class="' . implode(' ', (array)$classes) . '">';
						$ret .= $this->layoutButtons($layout_row['content'], $layout_row_layout, $layout_row['htmlOptions']??[]);
						$ret .= '</div><!--buttons -->' .  "\n";
						$ret .= '</div><!--row-->';
					} else {
						$ret .= $this->layoutButtons($layout_row['content'], $layout_row_layout,
													$layout_row['htmlOptions']??[]);
					}
				}
				break;
			case 'subtitle':
				$col_added = false;
				if (!$this->lastWasCol()) {
					$this->setLastCol($cols);
					$col_added = true;
					$ret .='<div class="' . $this->columnClasses($cols) . ' " style="display: flex; flex-direction: column">';
				}
				$ret .= $this->layoutSubtitle($layout_row['content'], $layout_row_layout, 'large', $layout_row['htmlOptions']??[]);
				break;
			case 'label&content':
			case 'form-control':
				$col_added = false;
				if (!$this->lastWasCol()) {
					$this->setLastCol($cols);
					$col_added = true;
					$ret .='<div class="' . $this->columnClasses($cols) . ' " style="display: flex; flex-direction: column">';
				}
				if (!array_key_exists('htmlOptions', $layout_row)) {
					$layout_row['htmlOptions'] = [];
				}
				Html::addCssClass($layout_row['htmlOptions'], $layout_row_type);
				// Html::addCssClass($layout_row['htmlOptions'], [ 'form-control readonly' ]);
				$ret .= $this->layoutContent($layout_row['label'],
					$layout_row['content'] ?: $layout_row['options']['emptyValue'] ?? '&nbsp;',
					$layout_row_layout, 'large', $layout_row['htmlOptions']);
				break;
			case 'html':
				$label = ArrayHelper::remove($layout_row, 'label', null);
				$classes = $this->widget_layout_horiz_config[$layout_row_layout]['full']['horizontalCssClasses'];
				$col_added = false;
				if (!$this->lastWasCol()) {
					$this->setLastCol($cols);
					$col_added = true;
					$ret .='<div class="' . $this->columnClasses($cols) . ' " style="display: flex; flex-direction: column">';
				}
				if (YII_ENV_DEV) {
					$classes['wrapper'][] = "{$layout_row_layout}xlarge";
				}
				Html::addCssClass($layout_row['htmlOptions'], 'row html');
				$content_options = [];
				Html::addCssClass($content_options, $classes['wrapper']);
				foreach ((array)$layout_row['content'] as $html_key => $html_content) {
					$ret .= Html::tag('div',
								Html::tag('div', $html_content, $content_options),
									$layout_row['htmlOptions']);
					$ret .= "<!--html row $html_key-->";
				}
				break;
			case 'ajax':
				$col_added = false;
				if (!$this->lastWasCol()) {
					$this->setLastCol($cols);
					$col_added = true;
					$ret .= '<div class="' . $this->columnClasses($cols) . '">';
				}
				$ret .= $this->layoutAjaxContent($layout_row, $layout_row_layout);
				break;
			}
			if ($col_added) {
				$this->removeLast();
				$ret .= '</div>';
			}
			if ($row_added) {
				$this->removeLast();
				$ret .= '</div>';
			}
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
				if ($row_style == 'grid-nolabels') {
					$widget_layout = 'full';
				}
				if ('static' == $widget_layout) {
					$classes = $this->widget_layout_horiz_config['static']['horizontalCssClasses'];
				} else if ('inline' == $layout_of_row) {
					$classes = $this->widget_layout_horiz_config['inline']['horizontalCssClasses'];
				} else {
					$classes = $this->widget_layout_horiz_config[$layout_of_row][$widget_layout]['horizontalCssClasses'];
				}
				if (!empty($widget->horizontalCssClasses)) {
					$classes = array_merge_recursive($classes, $widget->horizontalCssClasses);
				}
				if ($row_style == 'grid-nolabels') {
					$widget->enableLabel = false;
					$widget->labelOptions = [ 'class' => 'd-none' ];
					$classes[] = 'no-label';
				} else {
					$widget->labelOptions['class'] = implode(' ', $classes['label']) . " fld-$widget_name ";
					if (YII_ENV_DEV) {
						$widget->labelOptions['class'] .= " {$layout_of_row}x$widget_layout";
					}
				}
				$widget->wrapperOptions['class'] = implode(' ', $classes['wrapper']);
				if (YII_ENV_DEV) {
					$widget->wrapperOptions['class'] .= " {$layout_of_row}x$widget_layout";
				}
				Html::addCssClass($widget->options, 'form-group');
				if ($this->widget_painter) {
					$fs .= call_user_func($this->widget_painter, $widget, $classes, $indexf++);
				} else {
					$fs .= $widget->__toString();
				}
				break;
			case 'grid-cards':
				$lo = ['class' => "card-header fld-$widget_name"];
				$ro = ['class' => "card border-primary my-3 w-100"];
				// Same container marker as the 'grid' style above: without it a
				// field laid out in cards is the only one that never gets
				// .form-group, and any CSS or script keyed on it silently
				// misses the whole form.
				Html::addCssClass($widget->options, 'form-group');
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
						$classes['labelOptions']['class'] = 'd-none';
						$classes['wrapper'][] = 'no-label';
					} else {
						$classes['labelOptions']['class'] = implode(' ', $classes['label']) . " fld-$widget_name";
						if (YII_ENV_DEV) {
							$classes['labelOptions']['class'] .= " {$layout_of_row}x$widget_layout";
						}
					}
				}
				$classes['wrapperOptions']['class'] = implode(' ', $classes['wrapper']) . " fld-$widget_name";
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
					// No 'options' here on purpose: layoutOneField() feeds
					// RecordView, whose painter (layAttribute) reads only
					// labelOptions and wrapperOptions and drops the rest. A
					// read-only attribute has no form control to mark anyway.
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
									 string $widget_layout, array $contentOptions = []):string
	{
		$ret = '';
		$classes = $this->widget_layout_horiz_config[$layout_of_row][$widget_layout]['horizontalCssClasses'];
		$ret .= '<div class="row w-100 mb-3 form-group">';
		if (!empty($label)) {
			$ret .= Html::tag('label', $label, [ 'class' => $classes['label']]);
		}
		Html::addCssClass($classes['wrapper'], 'field');
		$ret .= Html::tag('div', Html::tag('div', $content, $contentOptions), ['class' => $classes['wrapper']]);
		$ret .= '</div>';
		return $ret;
	}

	protected function layoutSubtitle(string $content, string $layout_of_row,
		string $widget_layout, array $options = []):string
	{
		$ret = '';
		$tag = ArrayHelper::remove($options, 'tag', 'h2');
		$ret .= Html::tag($tag, $content, $options);
		return $ret;
	}
	public function layoutButtons(array $buttons, string $layout, array $options = []): string
	{
		$buttons = FormHelper::displayButtons($buttons);
		Html::addCssClass($options, 'btn-group-sm d-flex flex-row align-items-center ms-1');
		return <<<html
<div class="{$options['class']}">
$buttons
</div><!--buttons-->
html;
	}

    /**
     * Renders AJAX content
     */
    protected function layoutAjaxContent(array $layout_row, string $layout_of_row): string
    {
        $url = ArrayHelper::getValue($layout_row, 'content');
        if (!$url) {
            Yii::error("AJAX row type requires the URL as 'content'");
            return Html::tag('div', 'Missing URL for AJAX content', ['class' => 'alert alert-danger']);
        }
        $method = ArrayHelper::getValue($layout_row, 'method', 'GET');
        $data = ArrayHelper::getValue($layout_row, 'data', []);
		$title = ArrayHelper::getValue($layout_row, 'title', '');
        $loadingText = ArrayHelper::getValue($layout_row, 'loadingText', 'Loading...');
        $errorText = ArrayHelper::getValue($layout_row, 'errorText', 'Error loading content');
        $containerId = ArrayHelper::getValue($layout_row, 'containerId', 'ajax-container-' . uniqid());


        // Ensure URL is absolute
        if (is_array($url) && $title) {
			$url['title'] = $title;
		}
        $url = Url::to($url);

        $containerOptions = ArrayHelper::getValue($layout_row, 'containerOptions', []);
        Html::addCssClass($containerOptions, 'ajax-content-container');
        $containerOptions['id'] = $containerId;

        $options = ArrayHelper::getValue($layout_row, 'options', []);

        // Initial loading state
        $loadingContent = Html::tag('div',
            Html::tag('i', '', ['class' => 'fa-solid fa-spinner fa-spin fa-2x']) . "\n" . Html::tag('div', $loadingText, ['class' => 'mt-2']),
            ['class' => 'text-center p-4']
        );
        $content = Html::tag('div', $loadingContent, ['class' => 'ajax-loading']);

        $ret = Html::tag('div', $content, $containerOptions);

	// Escape all dynamic values for JS single-quoted string context
		$escapeJs = fn($v) => str_replace(["\\", "'", "\n", "\r"], ["\\\\", "\\'", "\\n", "\\r"], $v);
		$escapedUrl = $escapeJs($url);
		$escapedMethod = $escapeJs($method);
		$escapedContainerId = $escapeJs($containerId);
		$escapedErrorText = $escapeJs(Html::encode($errorText));
		$escapedOptions = $escapeJs(json_encode($options));

		$script = <<<JS

(function() {
	const container = document.getElementById('{$escapedContainerId}');
	if (!container) return;
	const options = JSON.parse('{$escapedOptions}');

	fetch('{$escapedUrl}', {
		method: '{$escapedMethod}',
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
			'Accept': 'text/html'
		}
	})
	.then(async response => {
		if (!response.ok) {
			let detail = '';
			try {
				const body = await response.text().catch(() => '');
				if (body) {
					try {
						const json = JSON.parse(body);
						detail = json.message || json.error || json.statusText || '';
					} catch {
						detail = body.substring(0, 500);
					}
				}
			} catch {}
			const err = new Error(detail
				? `HTTP \${response.status}: \${detail}`
				: `HTTP \${response.status} \${response.statusText}`);
			err.status = response.status;
			err.detail = detail;
			throw err;
		}
		return response.text();
	})
	.then(html => {
		if (options['remove-if-selector']) {
			const tmp = document.createElement('div');
			tmp.innerHTML = html;
			if (tmp.querySelector(options['remove-if-selector'])) {
				container.remove();
				return;
			}
		}
		container.innerHTML = html;
	})
	.catch(error => {
		const status = error.status || 0;
		console.error(`AJAX [\${status}]`, error.detail || error.message, error);
		let msg = '{$escapedErrorText}';
		if (status >= 500) {
			msg = 'Error recuperando los datos del servidor.';
		} else if (status === 404) {
			msg = 'El recurso solicitado no está disponible.';
		} else if (status === 403 || status === 401) {
			msg = 'No tienes permisos para acceder a `$title`.';
		} else if (status === 0) {
			msg = 'No se pudo conectar con el servidor. Comprueba tu conexión.';
		} else if (error.detail) {
			msg = error.detail;
		}
		container.innerHTML = '<div class="alert alert-danger">' + msg + '</div>';
	});
})();
JS;
		$ret .= Html::script($script, ['type' => 'text/javascript']);
        return $ret;
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

	private function lastWasRow()
	{
		$last = end($this->row_col);
		return intval($last??0) > 0;
	}
	private function lastWasCol()
	{
		$last = end($this->row_col);
		return intval($last??0) < 0;
	}
	private function setLastCol(int $cols)
	{
		$this->row_col[] = -$cols;
	}
	private function setLastRow($cols)
	{
		$this->row_col[] = $cols;
	}
	private function removeLast()
	{
		array_pop($this->row_col);
	}
	private function lastLevel(): string
	{
		return count($this->row_col) / 2;
	}
	private function noLast()
	{
		return count($this->row_col) == 0;
	}

} // form
