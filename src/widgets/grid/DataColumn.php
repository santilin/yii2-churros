<?php

namespace santilin\churros\widgets\grid;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\ActiveQueryInterface;
use yii\helpers\{ArrayHelper,Html,Inflector};


class DataColumn extends \yii\grid\DataColumn
{
	public const COMBINED_FILTER_NONE = 0;
	public const COMBINED_FILTER_FIRST = 1;
	public const COMBINED_FILTER_SECOND = 2;
	public const COMBINED_FILTER_BOTH = 3;

	public $summary;
    public string $combinedTemplate = '<div class="ps-2">{value1}<div class="small text-muted">{value2}</div></div>';
    public ?string $combinedColumn = null;
    public $combinedColumnInstance = null;
    public int $combinedFilterType = self::COMBINED_FILTER_NONE;


    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (is_array($this->filter)) {
            Html::removeCssClass($this->filterInputOptions, 'form-control');
            Html::addCssClass($this->filterInputOptions, 'form-select form-select-sm');
        } else {
            Html::addCssClass($this->filterInputOptions, 'form-control-sm');
        }
        parent::init();
    }

	// Da preferencia a las labels del searchmodel
    protected function getHeaderCellLabel()
    {
        $provider = $this->grid->dataProvider;

        if ($this->label === null) {
            if ($this->attribute === null) {
                $label = '';
            } elseif ($this->grid->filterModel !== null && $this->grid->filterModel instanceof Model) {
                $label = $this->grid->filterModel->getAttributeLabel($this->filterAttribute);
            } elseif ($provider instanceof ActiveDataProvider && $provider->query instanceof ActiveQueryInterface) {
                /* @var $modelClass Model */
                $modelClass = $provider->query->modelClass;
                $model = $modelClass::instance();
                $label = $model->getAttributeLabel($this->attribute);
            } elseif ($provider instanceof ArrayDataProvider && $provider->modelClass !== null) {
                /* @var $modelClass Model */
                $modelClass = $provider->modelClass;
                $model = $modelClass::instance();
                $label = $model->getAttributeLabel($this->attribute);
            } else {
                $models = $provider->getModels();
                if (($model = reset($models)) instanceof Model) {
                    /* @var $model Model */
                    $label = $model->getAttributeLabel($this->attribute);
                } else {
                    $label = Inflector::camel2words($this->attribute);
                }
            }
        } else {
            $label = $this->label;
        }

        return $label;
    }

    /**
     * {@inheritdoc}
     * Inherited to show the column thas has an error in DEVEL environment
     */
    public function renderDataCell($model, $key, $index)
    {
        if ($this->contentOptions instanceof \Closure) {
            $options = call_user_func($this->contentOptions, $model, $key, $index, $this);
        } else {
            $options = $this->contentOptions;
        }

        $format = $this->grid->formatOfColumn($this);
        if ($format !== '') {
            Html::addCssClass($options, "format-$format");
        }
        if (YII_ENV_DEV) {
            try {
                return Html::tag('td', $this->renderDataCellContent($model, $key, $index), $options);
            } catch (\yii\base\ErrorException $e) {
                \Yii::warning($e->getMessage() . " in column {$this->attribute}");
            } catch (\yii\base\InvalidArgumentException $e) {
                \Yii::warning($e->getMessage() . " in column {$this->attribute}");
            }
            return '<td class=error>###Error###</td>';
        } else {
            return Html::tag('td', $this->renderDataCellContent($model, $key, $index), $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        if ($this->combinedColumn !== null && $this->content === null) {
            $combinedColumn = $this->combinedColumnInstance ?? $this->grid->columns[$this->combinedColumn] ?? null;
            if ($combinedColumn instanceof self) {
                $val1 = $this->grid->formatter->format($this->getDataCellValue($model, $key, $index), $this->format);
                $val2 = $this->grid->formatter->format($combinedColumn->getDataCellValue($model, $key, $index), $combinedColumn->format);
                if (!empty($this->combinedTemplate)) {
                    return strtr($this->combinedTemplate, ['{value1}' => $val1, '{value2}' => $val2]);
                }
                return $values['value1'] . $values['value2'];
            }
        }
        return parent::renderDataCellContent($model, $key, $index);
    }

    /**
     * {@inheritdoc}
     */
    public function renderFilterCellContent()
    {
        $content = '';
        if ($this->combinedColumn === null) {
            $content = parent::renderFilterCellContent();
        } elseif ($this->combinedFilterType !== self::COMBINED_FILTER_NONE) {
            if ($this->combinedFilterType === self::COMBINED_FILTER_BOTH || $this->combinedFilterType === self::COMBINED_FILTER_FIRST) {
                $content = parent::renderFilterCellContent();
                if (!empty($this->combinedColumnInstance) && $this->combinedColumnInstance instanceof DataColumn) {
                    if ($this->combinedFilterType === self::COMBINED_FILTER_BOTH || $this->combinedFilterType === self::COMBINED_FILTER_SECOND) {
                        $content .= $this->combinedColumnInstance->renderFilterCellContent();
                    }
                }
            }
        }
        return $content;
    }

}
