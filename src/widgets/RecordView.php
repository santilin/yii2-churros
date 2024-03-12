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
use santilin\churros\ChurrosAsset;
use santilin\churros\helpers\{AppHelper,FormHelper};
use santilin\churros\widgets\WidgetLayer;
use santilin\churros\widgets\bs5\ActiveForm;

/**
 * RecordView displays the detail of a single data [[model]].
 *
 * @author Santilín <software@noviolento.es>
 */
class RecordView extends Widget
{
    public $model;
    public WidgetLayer $layer;
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
            $layer = new WidgetLayer($this->fieldsLayout, $this->attributes, [ $this, 'layAttribute' ], ActiveForm::FORM_FIELD_HORIZ_CLASSES);
			return '<div class="record-fields">'
                . $layer->layout('fields', $this->layout, $this->style)
				. '</div>';
		}
    }


    public function renderButtons()
    {
		$ret = '<div class="rv-btn-toolbar">';
		return FormHelper::displayButtons($this->buttons);
		return $ret . '</div>';
    }

    public function layAttribute($attr_key, array $widgetOptions, int $index): string
    {
        $labelOptions = ArrayHelper::remove($widgetOptions, 'labelOptions', []);
        $contentOptions = ArrayHelper::remove($widgetOptions, 'wrapperOptions', []);
        return $this->renderAttribute($attr_key, $labelOptions, $contentOptions, $index);
    }

    /**
     * Renders a single attribute.
     * @param array $attribute the specification of the attribute to be rendered.
     * @param int $index the zero-based index of the attribute in the [[attributes]] array
     * @return string the rendering result
     */
    public function renderAttribute($attr_key, $labelOptions, $contentOptions, $index)
    {
        if (is_string($attr_key)) {
            $attribute = $this->attributes[$attr_key];
        } else {
            $attribute = $attr_key;
        }
		$template = $attribute['template']??$this->fieldsTemplate;
        if (is_string($template)) {
            if ($labelOptions !== false) {
                $labelOptions = AppHelper::mergeAndConcat(['class'],
                    $labelOptions,
                    $attribute['labelOptions']??[]);
                $labelOptions = Html::renderTagAttributes($labelOptions);
            } else {
                $attribute['label'] = false;
            }
            $contentOptions = AppHelper::mergeAndConcat( ['class', 'style'],
                [ 'class' => "field" ],
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

}

