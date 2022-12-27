<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\widgets\grid\bs3;

use Yii;
use yii\helpers\Html;

class ActionColumn extends \yii\grid\ActionColumn
{
    public $template = '{view}&nbsp;{update}&nbsp;{delete}&nbsp;{duplicate}';
    public $duplicateOptions = [];
    public $customButtons = [];
    public $crudPerms = null;
    public $deleteOptions = [];
    public $hAlign = 'none';

    /**
     * Initializes the default button rendering callbacks.
     */
    protected function initDefaultButtons()
    {
		$this->hAlign = 'left';
        if( $this->crudPerms === null ) {
			$this->crudPerms = 'CRUD2';
		}
        if( strpos($this->crudPerms,'R') !== false ) {
			if( isset($this->customButtons['view']) ) {
				$this->buttons['view'] = $this->customButtons['view'];
				unset( $this->customButtons['view'] );
			} else {
				$this->initDefaultButton('view', 'eye-open',
					[ 'class' => 'action-button', 'title' => Yii::t('churros', 'View')]);
			}
		}
        if( strpos($this->crudPerms,'U') !== false ) {
			if( isset($this->customButtons['update']) ) {
				$this->buttons['update'] = $this->customButtons['update'];
				unset( $this->customButtons['update'] );
			} else {
				$this->initDefaultButton('update', 'pencil',
					[ 'title' => Yii::t('churros', 'Update')]);
			}
		}
        if( strpos($this->crudPerms,'D') !== false ) {
			if( isset($this->customButtons['delete']) ) {
				$this->buttons['delete'] = $this->customButtons['delete'];
				unset( $this->customButtons['delete'] );
			} else {
				$this->initDefaultButton('delete', 'trash',
					array_merge([
						'title' => Yii::t('churros', 'Delete'),
						'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
						'data-method' => 'post'
					], $this->deleteOptions));			}
		}
        if( strpos($this->crudPerms,'2') !== false ) {
			if( isset($this->customButtons['duplicate']) ) {
				$this->buttons['duplicate'] = $this->customButtons['duplicate'];
				unset( $this->customButtons['duplicate'] );
			} else {
				$this->initDefaultButton('duplicate', 'copy', [ 'title' => Yii::t('churros', 'Duplicate')]);
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
                switch ($name) {
                    case 'view':
                        $title = Yii::t('churros', 'View');
                        break;
                    case 'update':
                        $title = Yii::t('churros', 'Update');
                        break;
                    case 'delete':
                        $title = Yii::t('churros', 'Delete');
                        break;
                    case 'duplicate':
                        $title = Yii::t('churros', 'Duplicate');
                        break;
                    default:
                        $title = ucfirst($name);
                }
                $options = array_merge([
                    'title' => $title,
                    'aria-label' => $title,
                    'data-pjax' => '0',
                ], $this->buttonOptions, $additionalOptions );
                $icon = isset($this->icons[$iconName])
                    ? $this->icons[$iconName]
                    : Html::tag('span', '', ['class' => "glyphicon glyphicon-$iconName"]);
                return Html::a($icon, $url, $options);
            };
        }
    }

}
