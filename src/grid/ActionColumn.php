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
    /**
     * Initializes the default button rendering callbacks.
     */
    protected function initDefaultButtons()
    {
		parent::initDefaultButtons();
        $notBs3 = !$this->grid->isBs(3);
        $this->setDefaultButton('duplicate', Yii::t('kvgrid', 'Duplicate'), $notBs3 ? 'copy' : 'trash');
    }

}
