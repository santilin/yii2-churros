<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\grid;


use Yii;
class ActionColumn extends \kartik\grid\ActionColumn
{
    public $template = '{view}&nbsp;{update}&nbsp;{delete}&nbsp;{duplicate}';
    public $duplicateOptions = [];
    public $crudPerms = null;
    /**
     * Initializes the default button rendering callbacks.
     */
    protected function initDefaultButtons()
    {
		$this->hAlign = 'left';
        $notBs3 = !$this->grid->isBs(3);
        if( $this->crudPerms === null ) {
			$this->crudPerms = 'CRUD2';
		}
        if( strpos($this->crudPerms,'R') !== false ) {
			$this->setDefaultButton('view', Yii::t('churros', 'View'), $notBs3 ? 'eye' : 'eye-open');
		}
        if( strpos($this->crudPerms,'U') !== false ) {
			$this->setDefaultButton('update', Yii::t('churros', 'Update'), $notBs3 ? 'pencil-alt' : 'pencil');
		}
        if( strpos($this->crudPerms,'D') !== false ) {
			$this->setDefaultButton('delete', Yii::t('churros', 'Delete'), $notBs3 ? 'trash-alt' : 'trash');
		}
        if( strpos($this->crudPerms,'2') !== false ) {
			$this->setDefaultButton('duplicate', Yii::t('churros', 'Duplicate'), $notBs3 ? 'copy' : 'duplicate');
		}
    }

}
