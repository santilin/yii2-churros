<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\widgets\grid;

use Yii;
use yii\helpers\{Html,Url};
use santilin\churros\helpers\FormHelper;


class ActionColumn extends \yii\grid\ActionColumn
{
    public $template = '{view}&nbsp;{update}&nbsp;{delete}&nbsp;{duplicate}';
    public $customButtons = [];
    public $crudPerms = false;
    public $iconClassPrefix = 'bi bi';
	public $icons = [
		'view' => '<i class="bi bi-eye"></i>',
		'update' => '<i class="bi bi-pencil"></i>',
		'delete' => '<i class="bi bi-trash"></i>',
		'duplicate' => '<i class="bi bi-copy"></i>',
	];

    /**
     * Initializes the default button rendering callbacks.
     */
    protected function initDefaultButtons()
    {
        if( $this->crudPerms === null ) {
			$this->crudPerms = [ 'create', 'view', 'update', 'delete' ];
		}
		$options = $this->buttonOptions;
        if( $this->crudPerms === false || FormHelper::hasPermission($this->crudPerms, 'view') ) {
			if( !isset($this->buttons['view']) ) {
				$this->initDefaultButton('view', 'view', array_merge(
					[ 'title' => Yii::t('churros', 'View') ]));
			}
		}
        if( $this->crudPerms === false || FormHelper::hasPermission($this->crudPerms, 'update') ) {
			if( !isset($this->customButtons['update']) ) {
				$this->initDefaultButton('update', 'update', array_merge(
					[ 'title' => Yii::t('churros', 'Update') ]));
			}
		}
        if( $this->crudPerms === false || FormHelper::hasPermission($this->crudPerms, 'delete') ) {
			if( !isset($this->customButtons['delete']) ) {
				$this->initDefaultButton('delete', 'delete', array_merge(
					[ 'title' => Yii::t('churros', 'Delete'),
					  'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
					  'data-method' => 'post'
					]));
			}
		}
        if ($this->crudPerms === false || FormHelper::hasAllPermissions($this->crudPerms, ['duplicate'])) {
			if( !isset($this->customButtons['duplicate']) ) {
				$this->initDefaultButton('duplicate', 'duplicate', array_merge(
					[ 'title' => Yii::t('churros', 'Duplicate')]));
			}
		}
		foreach( $this->buttons as $index => $button ) {
			if (!str_contains($this->template, '{'.$index.'}')) {
				if ($this->template !='' ) {
					$this->template .= ' ';
				}
				$this->template .= '{'.$index.'}';
			}
		}
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
				Html::addCssClass($options, $this->buttonOptions['class']??[]);
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
