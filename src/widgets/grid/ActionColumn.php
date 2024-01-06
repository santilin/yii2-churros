<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\widgets\grid;

use Yii;
use yii\helpers\Html;
use santilin\churros\helpers\FormHelper;


/// @todo No usar &nbsp; usar style padding
class ActionColumn extends \yii\grid\ActionColumn
{
    public $template = '{view}&nbsp;{update}&nbsp;{delete}&nbsp;{duplicate}';
    public $customButtons = [];
    public $crudPerms = null;
    public $hAlign = 'none';
    public $iconClassPrefix = 'fa fa';

    /**
     * Initializes the default button rendering callbacks.
     */
    protected function initDefaultButtons()
    {
		$this->hAlign = 'left';
        if( $this->crudPerms === null ) {
			$this->crudPerms = [ 'create', 'view', 'update', 'index', 'delete', 'duplicate' ];
		}
        if( FormHelper::hasPermission($this->crudPerms, 'view') ) {
			if( isset($this->customButtons['view']) ) {
				$this->buttons['view'] = $this->customButtons['view'];
				unset( $this->customButtons['view'] );
			} else {
				$this->initDefaultButton('view', 'eye', array_merge(
					[ 'title' => Yii::t('churros', 'View') ]));
			}
		}
        if( FormHelper::hasPermission($this->crudPerms, 'update') ) {
			if( isset($this->customButtons['update']) ) {
				$this->buttons['update'] = $this->customButtons['update'];
				unset( $this->customButtons['update'] );
			} else {
				$this->initDefaultButton('update', 'pencil-alt', array_merge(
					[ 'title' => Yii::t('churros', 'Update') ]));
			}
		}
        if( FormHelper::hasPermission($this->crudPerms, 'delete') ) {
			if( isset($this->customButtons['delete']) ) {
				$this->buttons['delete'] = $this->customButtons['delete'];
				unset( $this->customButtons['delete'] );
			} else {
				$this->initDefaultButton('delete', 'trash-alt', array_merge(
					[ 'title' => Yii::t('churros', 'Delete'),
					  'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
					  'data-method' => 'post'
					]));
			}
		}
        if( FormHelper::hasAllPermissions($this->crudPerms, ['create','view']) ) {
			if( isset($this->customButtons['duplicate']) ) {
				$this->buttons['duplicate'] = $this->customButtons['duplicate'];
				unset( $this->customButtons['duplicate'] );
			} else {
				$this->initDefaultButton('duplicate', 'copy', array_merge(
					[ 'title' => Yii::t('churros', 'Duplicate')]));
			}
		}
		foreach( $this->customButtons as $index => $button ) {
			$this->template .= '&nbsp;{' . $index. '}';
			$this->buttons[$index] = $button;
		}
		$this->customButtons = []; // https://github.com/kartik-v/yii2-grid/issues/1047
    }

	/**
     * Initializes the default button rendering callback for single button.
     * @param string $name Button name as it's written in template
     * @param string $iconName The part of Bootstrap glyphicon class that makes it unique
     * @param array $additionalOptions Array of additional options
     * @since 2.0.11
     */
    protected function initDefaultButton($name, $iconName, $additionalOptions = [])
    {
        if (!isset($this->buttons[$name]) && strpos($this->template, '{' . $name . '}') !== false) {
            $this->buttons[$name] = function ($url, $model, $key) use ($name, $iconName, $additionalOptions) {
                $options = array_merge($this->buttonOptions, $additionalOptions);
                Html::addCssClass($options, $name);
				if( empty($options['aria-label']) ) {
					$options['aria-label'] = $options['title'];
				}
				$icon = isset($this->icons[$iconName])
                    ? $this->icons[$iconName]
                    : Html::tag('span', '', ['class' => "{$this->iconClassPrefix}-$iconName"]);
                return Html::a($icon, $url, $options);
            };
        }
    }

    protected function renderFilterCellContent()
    {
		$pagination = $this->grid->dataProvider->getPagination();
        if ($pagination === false || $this->grid->dataProvider->getCount() <= 0) {
            return '';
        }
		return Html::activeDropDownList($this->grid->filterModel, '_gridPageSize',
			[1=>1, 5=>5, 10 => 10, 20 => 20, 50 => 50, 100 => 100, 0 => 'Todo'],
			['id'=>'_grid_view_pageSize']);
    }

}
