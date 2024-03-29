<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\widgets;

use Yii;
use yii\base\Arrayable;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\i18n\Formatter;
use yii\bootstrap4\Tabs;
use santilin\churros\ChurrosAsset;
use santilin\churros\helpers\{AppHelper,FormHelper};
use santilin\churros\widgets\ActiveForm;

/**
 * RecordView displays the detail of a single data [[model]].
 *
 * @author Santilín <software@noviolento.es>
 */
class RecordView extends Widget
{
    public $model;
    public $attributes;
    public $template = '{header}{record}{footer}';
    public $headerTemplate = <<<html
<div class="panel panel-primary mb-2">
	<div class="panel-heading panel-primary">
		<div class="panel-title">
		{title}
		</div>
		<div class="panel-toolbar">
		{buttons}
		</div>
	</div>
</div>
html;
    public $fieldsTemplate = null;
    public $footerTemplate = '';
    public $options = [];
    public $style = 'grid'; // grid, table, grid-cards
    public $formatter;

    /**
     * @var string
     */
	public $layout = 'horizontal';

    public $fieldsLayout;

	public $title = null;
	public $buttons = [];

    /**
     * Initializes the detail view.
     * This method will initialize required property values.
     */
    public function init()
    {
        parent::init();

        if ($this->model === null) {
            throw new InvalidConfigException('Please specify the "model" property.');
        }
        if ($this->formatter === null) {
            $this->formatter = Yii::$app->getFormatter();
        } elseif (is_array($this->formatter)) {
            $this->formatter = Yii::createObject($this->formatter);
        }
        if (!$this->formatter instanceof Formatter) {
            throw new InvalidConfigException('The "formatter" property must be either a Format object or a configuration array.');
        }
        if ($this->fieldsTemplate === null) {
            if ($this->style == 'table') {
                $this->fieldsTemplate = '<tr><th{labelOptions}>{label}</th><td{contentOptions}>{value}</td></tr>';
            } elseif ($this->style == 'grid-cards') {
                $this->fieldsTemplate = '<div{labelOptions}>{label}</div><div class=card-body><div{contentOptions}>{value}</div></div>';
            } else {
                $this->fieldsTemplate = '<label{labelOptions}>{label}</label><div{contentOptions}>{value}</div>';
            }
        }
        $this->normalizeAttributes();

        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        Html::addCssClass($this->options, 'record-view');
    }

    /**
     * Renders the detail view.
     * This is the main entry of the whole detail view rendering.
     */
    public function run()
    {
        $view = $this->getView();
        ChurrosAsset::register($view);

        $title = $this->renderTitle();
        $record = $this->renderRecord();
        $buttons = $this->renderButtons();
        $header = strtr( $this->headerTemplate,
			[ '{record}' => $record, '{buttons}' => $buttons, '{title}' => $title ]);
		$footer = strtr( $this->footerTemplate,
			[ '{record}' => $record, '{buttons}' => $buttons, '{title}' => $title ]);
        return Html::tag('div', strtr( $this->template,
			[ '{header}' => $header, '{footer}' => $footer,
				'{title}' => $title, '{record}' => $record,
				'{buttons}' => $buttons ]), $this->options );
	}

	public function renderAsTable()
	{
        $rows = [];
        $i = 0;
        foreach ($this->attributes as $attribute) {
            $rows[] = $this->renderAttributeAsTable($attribute, $i++);
        }

        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'table');
        return Html::tag($tag, implode("\n", $rows), $options);
	}

	protected function renderAttributeAsTable($attribute, $index)
    {
        if (is_string($this->template)) {
            $labelOptions = Html::renderTagAttributes(ArrayHelper::getValue($attribute, 'labelOptions', []));
            $contentOptions = Html::renderTagAttributes(ArrayHelper::getValue($attribute, 'contentOptions', []));
            return strtr($this->template, [
                '{label}' => $attribute['label'],
                '{value}' => $this->formatter->format($attribute['value'], $attribute['format']),
                '{labelOptions}' => $labelOptions,
                '{contentOptions}' => $contentOptions,
            ]);
        }

        return call_user_func($this->template, $attribute, $index, $this);
    }

	public function renderTitle()
	{
		$ret = '';
		if( $this->title != null ) {
			$ret = $this->title;
		}
		return $ret;
	}

	public function renderRecord()
	{
		if ($this->style == 'table') {
			return $this->renderAsTable();
		} else {
			return '<div class="record-fields">'
				. $this->layoutAttributes()
				. '</div>';
		}
    }

    public function renderButtons()
    {
		$ret = '<div class="rv-btn-toolbar">';
		return FormHelper::displayButtons($this->buttons);
		return $ret . '</div>';
    }

    /**
     * Renders a single attribute.
     * @param array $attribute the specification of the attribute to be rendered.
     * @param int $index the zero-based index of the attribute in the [[attributes]] array
     * @return string the rendering result
     */
    protected function renderAttribute($attr_key, $labelOptions, $contentOptions, $index)
    {
		$attribute = $this->attributes[$attr_key];
        if ($labelOptions === false) {
            $attribute['label'] = false;
        }
		$template = $attribute['template']??$this->fieldsTemplate;
        if (is_string($template)) {
            if ($labelOptions !== false) {
                $labelOptions = AppHelper::mergeAndConcat(['class'],
                    $labelOptions,
                    $attribute['labelOptions']??[]);
                $labelOptions = Html::renderTagAttributes($labelOptions);
            }
            $contentOptions = array_merge(
				$contentOptions,
				$attribute['contentOptions']??[]);
			switch( $attribute['format'] ) {
			case 'integer':
			case 'currency':
			case 'decimal':
			case 'hours':
				Html::addCssClass($contentOptions, 'text-right');
				break;
			}
            $contentOptions = Html::renderTagAttributes($contentOptions);
            return strtr($template, [
                '{label}' => $attribute['label'],
                '{value}' => $this->formatter->format($attribute['value'], $attribute['format']),
                '{labelOptions}' => $labelOptions,
                '{contentOptions}' => $contentOptions,
            ]);
        }

        return call_user_func($template, $attribute, $labelOptions, $contentOptions, $index, $this);
    }

    /**
     * Normalizes the attribute specifications.
     * @throws InvalidConfigException
     */
    protected function normalizeAttributes()
    {
        if ($this->attributes === null) {
            if ($this->model instanceof Model) {
                $this->attributes = $this->model->attributes();
            } elseif (is_object($this->model)) {
                $this->attributes = $this->model instanceof Arrayable ? array_keys($this->model->toArray()) : array_keys(get_object_vars($this->model));
            } elseif (is_array($this->model)) {
                $this->attributes = array_keys($this->model);
            } else {
                throw new InvalidConfigException('The "model" property must be either an array or an object.');
            }
            sort($this->attributes);
        }

        foreach ($this->attributes as $i => $attribute) {
            if (is_string($attribute)) {
                if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $attribute, $matches)) {
                    throw new InvalidConfigException('The attribute must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
                }
                $attribute = [
                    'attribute' => $matches[1],
                    'format' => isset($matches[3]) ? $matches[3] : 'text',
                    'label' => isset($matches[5]) ? $matches[5] : null,
                ];
            }
			if (!is_array($attribute)) {
                throw new InvalidConfigException('The attribute configuration must be an array or a closure.');
            }

            if (isset($attribute['visible']) && !$attribute['visible']) {
                unset($this->attributes[$i]);
                continue;
            }

            if (!isset($attribute['format'])) {
                $attribute['format'] = 'text';
            }
            if (isset($attribute['attribute'])) {
                $attributeName = $attribute['attribute'];
                if (!isset($attribute['label'])) {
                    $attribute['label'] = $this->model instanceof Model ? $this->model->getAttributeLabel($attributeName) : Inflector::camel2words($attributeName, true);
                }
                if (!array_key_exists('value', $attribute)) {
                    $attribute['value'] = ArrayHelper::getValue($this->model, $attributeName);
                }
            } elseif (!isset($attribute['label']) || !array_key_exists('value', $attribute)) {
                throw new InvalidConfigException('The attribute configuration requires the "attribute" element to determine the value and display label.');
            }

            if ($attribute['value'] instanceof \Closure) {
                $attribute['value'] = call_user_func($attribute['value'], $this->model, $this);
            }

            $this->attributes[$i] = $attribute;
        }
    }

	protected function layoutFields(array $layout_rows, array $view_fields, array $parent_options = []): string
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
                                'content' => $this->layoutFields($content['fields'], $view_fields,
                                    ['layout' => $content['layout']??$layout_of_row, 'style' => $content['style']??$parent_style]),
                            ];
                        }
                        $ret .= Tabs::widget([ 'items' => $tab_items ]);
                        break;
                    case 'row':
                        foreach ($row_layout['content'] as $kc => $content) {
                            $ret .= '<div class="' . $this->columnClasses($cols) . '">';
                            $ret .= $this->layoutFields([$content], $view_fields,
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
                foreach ($row_layout['fields'] as $view_field) {
                    if (!empty($view_fields[$view_field])) {
                        $fld_layout=$view_fields[$view_field]['layout']??'large';
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
                                if ($row_style == 'grid-nolabels') {
                                    $lo = false;
                                } else {
                                    $lo = ['class' => "label-$view_field " . implode(' ', $classes['label'])];
                                }
                                $co = [ 'class' => "field field-$view_field " . $classes['wrapper'] ];
                                $col_classes = $this->columnClasses($fld_layout == 'full' ? 1 : $cols);
                                if ($col_classes == 'col col-12') {
                                    $fs .= $this->renderAttribute($view_field, $lo, $co, $indexf++);
                                } else {
                                    $fs .= "<div class=\"$col_classes\">";
                                    $fs .= $this->renderAttribute($view_field, $lo, $co, $indexf++);
                                    $fs .= '</div>';
                                }
                                break;
                            case 'grid-cards':
                                $col_classes = $this->columnClasses($fld_layout == 'full' ? 1 : $cols);
                                $fs.= '<div class="col ' . $col_classes . '">';
                                $ro = ['class' => "card field-container border-primary my-3 w-100"];
                                $lo = ['class' => "card-header label-$view_field"];
                                $co = ['class' => 'card-text'];
                                $fs .= '<div' . Html::renderTagAttributes($ro) . '>';
                                $fs .= $this->renderAttribute($view_field, $lo, $co, $indexf++);
                                $fs .= "</div></div><!--$view_field-->";
                                break;
                        }
                        $nf++;
                    } else {
                        throw new InvalidConfigException($view_field . ": 'fields' not found in row layout");
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


    protected function layoutAttributes()
	{
		if (empty($this->fieldsLayout) || is_string($this->fieldsLayout)) {
			$this->fieldsLayout = [
                [
                    'type' => 'fields',
                    'layout' => $this->fieldsLayout,
                    'fields' => array_keys($this->attributes),
                ]
			];
		}
 		return $this->layoutFields($this->fieldsLayout, $this->attributes, [
            'layout' => $this->layout == 'horizontal' ? '1col' : $this->layout,
            'style' => $this->style,
        ]);
	}

	protected function columnClasses(int $cols): string
    {
        switch( $cols ) {
            case 1:
                return 'col col-12';
            case 2:
                $col = $col_sm = 12;
                $col_md = $col_lg = $col_xl = 6;
                break;
            case 3:
                return 'col col-4';
            case 4:
            default:
                return 'col col-3';
        }
        return "col col-$col col-sm-$col_sm col-md-$col_md col-lg-$col_lg col-xl-$col_xl";
    }

}

