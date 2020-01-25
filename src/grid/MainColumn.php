<?php

namespace santilin\churros\grid;

use kartik\grid\DataColumn;
use yii\helpers\Html;
use yii\helpers\Url;
use santilin\churros\helpers\AppHelper;

/**
 * Class LinkColumn
 * To add an UrlColumn to the gridview, add it to the [[GridView::columns|columns]] configuration as follows:
 *   ...
 *   [
 *       'class' => 'en-develop\grid\LinkColumn',
 *       'attribute' => 'yourAttribute',
 *       'url' => 'your url',
 *       'params' => [
 *           'urlParam' => 'modelAttribute'
 *       ],
 *       'linkOptions' => []
 *   ]
 *  ...
 *
 * @package edevelop\grid
 */
class MainColumn extends DataColumn
{
    /**
     * link
     * @var
     */
    public $controller = null;
    /**
     * defaultAction
     * @var
     */
    public $defaultAction = 'view';
    /**
     * Params for link
     * @var
     */
    public $params = [];
    /**
     * Options for link settings
     * @var
     */
    public $linkOptions = [];
    /**
     * @var string
     */
    public $format = 'raw';

    /**
     * @var model
     */
    public $parent = null;

    /**
     * {@inheritdoc}
     * @throws \yii\base\InvalidArgumentException
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        return Html::a(
			$this->grid->formatter->format(
				$this->getDataCellValue($model, $key, $index), $this->format),
            $this->generateUrl($model, $key, $index),
            $this->linkOptions
        );
    }

    /**
     * @param $model
     * @throws \yii\base\InvalidArgumentException
     * @return string
     */
    protected function generateUrl($model, $key, $index)
    {
        $params = is_array($key) ? $key : ['id' => (string) $key];
        if( $this->controller ) {
			return Url::to($this->controller . '/' . $this->defaultAction . "?id=$key");
		} else {
			$params[0] = $this->defaultAction;
			return Url::toRoute($params);
		}
    }
}
