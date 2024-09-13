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
    public $crudPerms = [];
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
        if( FormHelper::hasPermission($this->crudPerms, 'view') ) {
			if( !isset($this->buttons['view']) ) {
				$this->initDefaultButton('view', 'view', array_merge(
					[ 'title' => Yii::t('churros', 'View') ]));
			}
		}
        if( FormHelper::hasPermission($this->crudPerms, 'update') ) {
			if( !isset($this->buttons['update']) ) {
				$this->initDefaultButton('update', 'update', array_merge(
					[ 'title' => Yii::t('churros', 'Update') ]));
			}
		}
        if( FormHelper::hasPermission($this->crudPerms, 'delete') ) {
			if( !isset($this->buttons['delete']) ) {
				$this->initDefaultButton('delete', 'delete', array_merge(
					[ 'title' => Yii::t('churros', 'Delete'),
					  'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
					  'data-method' => 'post'
					]));
			}
		}
        if (FormHelper::hasAllPermissions($this->crudPerms, ['duplicate'])) {
			if( !isset($this->buttons['duplicate']) ) {
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

    /**
	 * Creates a URL for the given action and model.
	 * This method is called for each button and each row.
	 * @param string $action the button name (or action ID)
	 * @param \yii\db\ActiveRecordInterface $model the data model
	 * @param mixed $key the key associated with the data model
	 * @param int $index the current row index
	 * @return string the created URL
	 */
	public function createUrl($action, $model, $key, $index)
	{
		if (is_callable($this->urlCreator, true)) { // Added true so that an array can be passed
			return call_user_func($this->urlCreator, $action, $model, $key, $index, $this);
		}

		$params = is_array($key) ? $key : ['id' => (string) $key];
		$params[0] = $this->controller ? $this->controller . '/' . $action : $action;

		return Url::toRoute($params);
	}

}
