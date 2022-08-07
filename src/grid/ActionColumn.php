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
    public $crudPerms = 'CRUDd';
    /**
     * Initializes the default button rendering callbacks.
     */
    protected function initDefaultButtons()
    {
        $notBs3 = !$this->grid->isBs(3);
        if( $this->crudPerms == '' ) {
			$this->crudPerms = 'CRUDd';
		}
        if( strpos($this->crudPerms,'R') !== false ) {
			$this->setDefaultButton('view', Yii::t('kvgrid', 'View'), $notBs3 ? 'eye' : 'eye-open');
		}
        if( strpos($this->crudPerms,'U') !== false ) {
			$this->setDefaultButton('update', Yii::t('kvgrid', 'Update'), $notBs3 ? 'pencil-alt' : 'pencil');
		}
        if( strpos($this->crudPerms,'D') !== false ) {
			$this->setDefaultButton('delete', Yii::t('kvgrid', 'Delete'), $notBs3 ? 'trash-alt' : 'trash');
		}
        if( strpos($this->crudPerms,'d') !== false ) {
			$this->setDefaultButton('duplicate', Yii::t('kvgrid', 'Duplicate'), $notBs3 ? 'copy' : 'copy');
		}
    }

}
