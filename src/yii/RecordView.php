<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\yii;

use Yii;
use yii\base\Arrayable;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\i18n\Formatter;
use santilin\churros\yii\RecordViewAsset;
use santilin\churros\helpers\AppHelper;


/**
 * RecordView displays the detail of a single data [[model]].
 *
 * @author Santilín <software@noviolento.es>
 */
class RecordView extends Widget
{
    public $model;
    public $attributes;
    public $fieldsTemplate = '<label{captionOptions}>{label}</label><div{contentOptions}>{value}</div>';
    public $headerTemplate = <<<html
<div class="panel panel-primary">
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
    public $footerTemplate = '';
    public $template = '{header}{record}{footer}';
	public $asTableTemplate = '<tr><th{captionOptions}>{label}</th><td{contentOptions}>{value}</td></tr>';
    public $options = ['class' => 'record-view'];
    public $formatter;

    /**
     * @var string
     */
	public $layout = 'horizontal';

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
        $this->normalizeAttributes();

        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
    }

    /**
     * Renders the detail view.
     * This is the main entry of the whole detail view rendering.
     */
    public function run()
    {
        $view = $this->getView();
        RecordViewAsset::register($view);

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
        if (is_string($this->asTableTemplate)) {
            $captionOptions = Html::renderTagAttributes(ArrayHelper::getValue($attribute, 'captionOptions', []));
            $contentOptions = Html::renderTagAttributes(ArrayHelper::getValue($attribute, 'contentOptions', []));
            return strtr($this->asTableTemplate, [
                '{label}' => $attribute['label'],
                '{value}' => $this->formatter->format($attribute['value'], $attribute['format']),
                '{captionOptions}' => $captionOptions,
                '{contentOptions}' => $contentOptions,
            ]);
        }

        return call_user_func($this->asTableTemplate, $attribute, $index, $this);
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
		if( $this->layout == 'table' ) {
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
		if( count($this->buttons ) ) {
			foreach( $this->buttons as $button ) {
				$ret .= $button;
			}
		}
		return $ret . '</div>';
    }

    /**
     * Renders a single attribute.
     * @param array $attribute the specification of the attribute to be rendered.
     * @param int $index the zero-based index of the attribute in the [[attributes]] array
     * @return string the rendering result
     */
    protected function renderAttribute($attr_key, $captionOptions, $contentOptions, $index)
    {
		$attribute = $this->attributes[$attr_key];
        if (is_string($this->fieldsTemplate)) {
             $captionOptions = AppHelper::mergeAndConcat(['class'], $captionOptions,
				ArrayHelper::getValue($attribute, 'captionOptions', [ 'class' => 'rv-label'])
			);
            $captionOptions = Html::renderTagAttributes($captionOptions);
            $contentOptions = array_merge(
				ArrayHelper::getValue($attribute, 'contentOptions', [ 'class' => 'rv-field']),
				$contentOptions);
			switch( $attribute['format'] ) {
			case 'integer':
			case 'currency':
			case 'decimal':
			case 'hours':
				Html::addCssClass($contentOptions, 'text-right');
				break;
			}
            $contentOptions = Html::renderTagAttributes($contentOptions);
            return strtr($this->fieldsTemplate, [
                '{label}' => $attribute['label'],
                '{value}' => $this->formatter->format($attribute['value'], $attribute['format']),
                '{captionOptions}' => $captionOptions,
                '{contentOptions}' => $contentOptions,
            ]);
        }

        return call_user_func($this->template, $attribute, $captionOptions, $contentOptions, $index, $this);
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

    protected function layoutAttributes()
	{
		$ret = '';
		$layout_rows = [];
		$captionOptions = $contentOptions = [];
		if( !is_array($this->layout) ) {
			$ncols = 1;
			switch( $this->layout ) {
			case "3cols":
				$ncols++;
			case "2cols":
				$ncols++;
				$layout_rows = [];
				$row = [];
				$nc = $ncols;
				foreach( array_keys($this->attributes) as $key ) {
					if ($nc-- == 0 ) {
						$nc = $ncols-1;
						$layout_rows[] = $row;
						$row = [$key];
					} else {
						$row[] = $key;
					}
				}
				if ($nc >= 0 ) {
					$layout_rows[] = $row;
				}
				break;
			case "horizontal": // 1col
			case 'inline':
				foreach( array_keys($this->attributes) as $key ) {
					$layout_rows[] = [$key];
				}
				break;
			}
		}
		if( count($layout_rows) ) {
			$index = 0; // ??
			foreach($layout_rows as $lrow ) {
				$c = count($lrow);
				$row = '';
				$rowOptions = [ 'class' => "field-container cols-$c"];
				foreach( $lrow as $attribute ) {
					$rowOptions = AppHelper::mergeAndConcat(['class'],
						$rowOptions,
						$this->attributes[$attribute]['rowOptions']??[]);
					$lo = [ 'class' => "rv-label field-$attribute" ];
					$row .= $this->renderAttribute($attribute, $lo, [], $index);
				}
				$ret .= '<div' . Html::renderTagAttributes($rowOptions) 
					. ">$row</div>";
			}
		}
		return $ret;
	}


}

