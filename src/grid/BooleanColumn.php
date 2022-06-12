<?php
/*
 * This file is part of the 2amigos/yii2-grid-view-library project.
 * (c) 2amigOS! <http://2amigos.us/>
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
namespace santilin\churros\grid;

use Yii;
use yii\base\Model;
use yii\bootstrap\Html;

class BooleanColumn extends \yii\grid\DataColumn
{
    /**
     * @var string $onTrue the contents to display when value is true.
     */
    public $onTrue = '<span class="glyphicon glyphicon-ok text-success"></span>';
    /**
     * @var string $onFalse the contents to display when value is false.
     */
    public $onFalse = '<span class="glyphicon glyphicon-remove text-danger"></span>';
    /**
     * @inheritdoc
     */
    public $format = 'html';
    /**
     * @var bool whether to display empty values with the $onFalse contents.
     */
    public $treatEmptyAsFalse = true;

        /**
     * {@inheritdoc}
     */
    protected function renderFilterCellContent()
    {
		$model = $this->grid->filterModel;
        if ($model instanceof Model && $this->attribute !== null && $model->isAttributeActive($this->attribute)) {
			if ($model->hasErrors($this->attribute)) {
				Html::addCssClass($this->filterOptions, 'has-error');
				$error = ' ' . Html::error($model, $this->attribute, $this->grid->filterErrorOptions);
			} else {
				$error = '';
			}
			$options = array_merge(['prompt' => Yii::t('churros', 'All')], $this->filterInputOptions);
			if( $model->{$this->attribute} === '' ) {
				$options['value'] = null; // Select 'All'
			}
			return Html::activeDropDownList($model, $this->attribute, [
				1 => $this->grid->formatter->booleanFormat[1],
				0 => $this->grid->formatter->booleanFormat[0],
			], $options) . $error;
		}
        return parent::renderFilterCellContent();
    }

    /**
     * @inheritdoc
     */
    public function renderDataCell($model, $key, $index)
    {
        if ($this->contentOptions instanceof \Closure) {
            $options = call_user_func($this->contentOptions, $model, $key, $index, $this);
        } else {
            $options = $this->contentOptions;
        }
        Html::addCssClass($options, 'text-center');
        return Html::tag('td', $this->renderDataCellContent($model, $key, $index), $options);
    }
    /**
     * @inheritdoc
     */
    public function getDataCellValue($model, $key, $index)
    {
        $value = parent::getDataCellValue($model, $key, $index);
        if( is_bool($value) ) {
            return $value ? $this->onTrue : $this->onFalse;
		}
        if (!empty($value)) {
            return $value ? $this->onTrue : $this->onFalse;
        }
        return $this->treatEmptyAsFalse ? $this->onFalse : $value;
    }
}
