<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\grid;


use Yii;
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
//     protected function initDefaultButtons()
//     {
// 		$this->hAlign = 'left';
//         $notBs3 = true; // !$this->grid->isBs(3);
//         if( $this->crudPerms === null ) {
// 			$this->crudPerms = 'CRUD2';
// 		}
//         if( strpos($this->crudPerms,'R') !== false ) {
// 			if( isset($this->customButtons['view']) ) {
// 				$this->buttons['view'] = $this->customButtons['view'];
// 				unset( $this->customButtons['view'] );
// 			} else {
// 				$this->setDefaultButton('view', Yii::t('churros', 'View'), $notBs3 ? 'eye' : 'eye-open');
// 			}
// 		}
//         if( strpos($this->crudPerms,'U') !== false ) {
// 			if( isset($this->customButtons['update']) ) {
// 				$this->buttons['update'] = $this->customButtons['update'];
// 				unset( $this->customButtons['update'] );
// 			} else {
// 				$this->setDefaultButton('update', Yii::t('churros', 'Update'), $notBs3 ? 'pencil-alt' : 'pencil');
// 			}
// 		}
//         if( strpos($this->crudPerms,'D') !== false ) {
// 			if( isset($this->customButtons['delete']) ) {
// 				$this->buttons['delete'] = $this->customButtons['delete'];
// 				unset( $this->customButtons['delete'] );
// 			} else {
// 				$this->setDefaultButton('delete', Yii::t('churros', 'Delete'), $notBs3 ? 'trash-alt' : 'trash');
// 			}
// 		}
//         if( strpos($this->crudPerms,'2') !== false ) {
// 			if( isset($this->customButtons['duplicate']) ) {
// 				$this->buttons['duplicate'] = $this->customButtons['duplicate'];
// 				unset( $this->customButtons['duplicate'] );
// 			} else {
// 				$this->setDefaultButton('duplicate', Yii::t('churros', 'Duplicate'), $notBs3 ? 'copy' : 'duplicate');
// 			}
// 		}
// 		foreach( $this->customButtons as $index => $button ) {
// 			$this->template .= '&nbsp;{' . $index. '}';
// 			$this->buttons[$index] = $button;
// 		}
// 		$this->customButtons = []; // https://github.com/kartik-v/yii2-grid/issues/1047
//     }

}
