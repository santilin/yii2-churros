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
     * Inherited to show the column thas has an error
     */
    protected function renderDataCellContent($model, $key, $index)
    {
try {
        if ($this->content === null) {
            return $this->grid->formatter->format($this->getDataCellValue($model, $key, $index), $this->format);
        }

        return parent::renderDataCellContent($model, $key, $index);
} catch (\yii\base\ErrorException $e) {
    throw new \yii\base\ErrorException($e->getMessage() . ":{$this->attribute}=" . json_encode($model->{$this->attribute}),
        $e->getCode(), $e->getSeverity(), $e->getFile(), $e->getLine(), $e->getPrevious());
} catch (\yii\base\InvalidArgumentException $e) {
    throw new \yii\base\ErrorException($e->getMessage() . ":{$this->attribute}=" . json_encode($model->{$this->attribute}),
    $e->getCode(), 0, $e->getFile(), $e->getLine(), $e->getPrevious());
}

    }


}
