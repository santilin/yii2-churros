<?php

namespace santilin\churros\widgets\grid;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\ActiveQueryInterface;
use yii\helpers\{ArrayHelper,Html,Inflector};


class DataColumn extends \yii\grid\DataColumn
{
    public $summary;
    public string $joinedTemplate = '<div class="ps-2">{attr1}<div class="small text-muted">{attr2}</div></div>';
    public string $joinedColumn;
    public $joinedColumnInstance = null;


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
        if (!empty($this->joinedColumn) && $this->content === null) {
            $joinedColumn = $this->joinedColumnInstance ?? $this->grid->columns[$this->joinedColumn] ?? null;
            if ($joinedColumn instanceof self) {
                $values = [];
                // Use the value key from column definition
                $values['attr1'] = $this->getDataCellValue($model, $key, $index, $this);
                $values['attr2'] = $joinedColumn->getDataCellValue($model, $key, $index);
                if (!empty($this->joinedTemplate)) {
                    $result = $this->joinedTemplate;
                    foreach ($values as $attr => $value) {
                        $result = str_replace("{{$attr}}", $value, $result);
                    }
                    return $result;
                }
                // If no joinedTemplate, just combine values
                return $values['attr1'] . $values['attr2'];
            }
        }
        return parent::renderDataCellContent($model, $key, $index);
    }

    /**
     * {@inheritdoc}
     */
    public function renderFilterCellContent()
    {
        $content = parent::renderFilterCellContent();
        // If there's a joined column, render its filter as well
        if (!empty($this->joinedColumnInstance) && $this->joinedColumnInstance instanceof DataColumn) {
            $content .= $this->joinedColumnInstance->renderFilterCellContent();
        }
        return $content;
    }

}
