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


/**
 * DetailView displays the detail of a single data [[model]].
 *
 * DetailView is best used for displaying a model in a regular format (e.g. each model attribute
 * is displayed as a row in a table.) The model can be either an instance of [[Model]]
 * or an associative array.
 *
 * DetailView uses the [[attributes]] property to determines which model attributes
 * should be displayed and how they should be formatted.
 *
 * A typical usage of DetailView is as follows:
 *
 * ```php
 * $ret .= DetailView::widget([
 *     'model' => $model,
 *     'attributes' => [
 *         'title',               // title attribute (in plain text)
 *         'description:html',    // description attribute in HTML
 *         [                      // the owner name of the model
 *             'label' => 'Owner',
 *             'value' => $model->owner->name,
 *         ],
 *         'created_at:datetime', // creation date formatted as datetime
 *     ],
 * ]);
 * ```
 *
 * For more details and usage information on DetailView, see the [guide article on data widgets](guide:output-data-widgets).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class RecordView extends Widget
{
    public $model;
    public $attributes;
    public $template = '<label{captionOptions}>{label}</label><div{contentOptions}>{value}</div>';
    public $options = ['class' => 'detail-view'];
    public $formatter;

    /**
     * @var string
     */
	public $layout = 'horizontal';

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

        $options = $this->options;
		return "<div" .
			Html::renderTagAttributes($options)
			. '><div class="form-fields">'
 			. $this->layoutAttributes()
			. '</div></div>';
    }

    /**
     * Renders a single attribute.
     * @param array $attribute the specification of the attribute to be rendered.
     * @param int $index the zero-based index of the attribute in the [[attributes]] array
     * @return string the rendering result
     */
    protected function renderAttribute($attr_key, $index)
    {
		$attribute = $this->attributes[$attr_key];
        if (is_string($this->template)) {
            $captionOptions = Html::renderTagAttributes(ArrayHelper::getValue($attribute, 'captionOptions', [ 'class' => 'control-label'] ));
            $contentOptions = Html::renderTagAttributes(ArrayHelper::getValue($attribute, 'contentOptions', [ 'class' => 'form-control'] ));
            return strtr($this->template, [
                '{label}' => $attribute['label'],
                '{value}' => $this->formatter->format($attribute['value'], $attribute['format']),
                '{captionOptions}' => $captionOptions,
                '{contentOptions}' => $contentOptions,
            ]);
        }

        return call_user_func($this->template, $attribute, $index, $this);
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
                throw new InvalidConfigException('The attribute configuration must be an array.');
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
		if( !is_array($this->layout) ) {
			switch( $this->layout ) {
			case "2cols":
				$layout_rows = [];
				$row = [];
				foreach( array_keys($this->attributes) as $key ) {
					switch(count($row)) {
					case 2:
						$layout_rows[] = $row;
						$row = [];
					case 0:
						$row[0] = $key;
						break;
					case 1:
						$row[1] = $key;
						break;
					}
				}
				if( count($row) != 0 ) {
					$layout_rows[] = $row;
				}
				break;
			case 'horizontal':
			case 'inline':
				foreach( array_keys($this->attributes) as $key ) {
					$layout_rows[] = [$key];
				}
				break;
			}
		}
		if( count($layout_rows) ) {
			$index = 0;
			foreach($layout_rows as $lrow ) {
				switch(count($lrow)) {
				case 1:
					$ret .= '<div class="form-group">';
					$ret .= $this->renderAttribute($lrow[0], $index);
					$ret .= "</div>";
					break;
				case 2:
					$ret .= '<div class="row">';
					$ret .= '<div class="col-sm-6">';
					$ret .= $this->renderAttribute($lrow[0], $index);
					$ret .= "</div>";
					$ret .= '<div class="col-sm-6">';
					$ret .= $this->renderAttribute($lrow[1], $index);
					$ret .= "</div>";
					$ret .= "</div>";
					break;
				case 3:
					$ret .= '<div class="row">';
					$ret .= '<div class="col-sm-4">';
					$ret .= $this->renderAttribute($lrow[0], $index);
					$ret .= "</div>";
					$ret .= '<div class="col-sm-4">';
					$ret .= $this->renderAttribute($lrow[1], $index);
					$ret .= "</div>";
					$ret .= '<div class="col-sm-4">';
					$ret .= $this->renderAttribute($lrow[2], $index);
					$ret .= "</div>";
					$ret .= "</div>";
					break;
				}
			}
		}
		return $ret;
	}


}

