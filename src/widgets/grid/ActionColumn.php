<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\widgets\grid;

use Yii;
use yii\helpers\{Html};
/// @todo trim o simflify_white_space del content final para cuando no se generan algunos botones
class ActionColumn extends \yii\grid\ActionColumn
{
    public $template = '{view}&nbsp;{update}&nbsp;{delete}&nbsp;{duplicate}';
    public $customButtons = [];
    public $crudPerms = null;
    public $deleteOptions = [ 'class' => 'delete' ];
	public $updateOptions = [ 'class' => 'update' ];
    public $viewOptions = [ 'class' => 'view' ];
    public $duplicateOptions = [ 'class' => 'duplicate' ];
    public $hAlign = 'none';
    public $iconClassPrefix = 'fa fa';

    public function __construct($config = [])
    {
		parent::__construct($config);
		if( !isset($this->icons['view']) && isset($this->icons['eye-open']) ) {
			$this->icons['view'] = $this->icons['eye-open'];
		}
		if( !isset($this->icons['update']) &&isset($this->icons['pencil']) ) {
			$this->icons['update'] = $this->icons['pencil'];
		}
		if( !isset($this->icons['delete']) && isset($this->icons['trash']) ) {
			$this->icons['delete'] = $this->icons['trash'];
		}
	}

    /**
     * Initializes the default button rendering callbacks.
     */
    protected function initDefaultButtons()
    {
		$this->hAlign = 'left';
        if( $this->crudPerms === null ) {
			$this->crudPerms = 'CRUD2';
		}
		$options = $this->buttonOptions;
        if( strpos($this->crudPerms,'R') !== false ) {
			if( isset($this->customButtons['view']) ) {
				$this->buttons['view'] = $this->customButtons['view'];
				unset( $this->customButtons['view'] );
			} else {
				$this->initDefaultButton('view', 'view', array_merge(
					[ 'title' => Yii::t('churros', 'View') ], $this->viewOptions));
			}
		}
        if( strpos($this->crudPerms,'U') !== false ) {
			if( isset($this->customButtons['update']) ) {
				$this->buttons['update'] = $this->customButtons['update'];
				unset( $this->customButtons['update'] );
			} else {
				$this->initDefaultButton('update', 'update', array_merge(
					[ 'title' => Yii::t('churros', 'Update') ], $this->updateOptions));
			}
		}
        if( strpos($this->crudPerms,'D') !== false ) {
			if( isset($this->customButtons['delete']) ) {
				$this->buttons['delete'] = $this->customButtons['delete'];
				unset( $this->customButtons['delete'] );
			} else {
				$this->initDefaultButton('delete', 'delete', array_merge(
					[ 'title' => Yii::t('churros', 'Delete'),
					  'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
					  'data-method' => 'post'
					], $this->deleteOptions));
			}
		}
        if( strpos($this->crudPerms,'2') !== false ) {
			if( isset($this->customButtons['duplicate']) ) {
				$this->buttons['duplicate'] = $this->customButtons['duplicate'];
				unset( $this->customButtons['duplicate'] );
			} else {
				$this->initDefaultButton('duplicate', 'copy', array_merge(
					[ 'title' => Yii::t('churros', 'Duplicate')], $this->duplicateOptions));
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
                Html::addCssClass($options, $this->buttonOptions['class']??[]);
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
}
