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
        $notBs3 = !$this->grid->isBs(3);
        $this->setDefaultButton('view', Yii::t('kvgrid', 'View'), $notBs3 ? 'eye' : 'eye-open');
        $this->setDefaultButton('update', Yii::t('kvgrid', 'Update'), $notBs3 ? 'pencil' : 'pencil');
        $this->setDefaultButton('delete', Yii::t('kvgrid', 'Delete'), $notBs3 ? 'trash' : 'trash');
        $this->setDefaultButton('duplicate', Yii::t('kvgrid', 'Duplicate'), $notBs3 ? 'copy' : 'trash');
    }

    protected function renderDataCellContent($model, $key, $index)
	{
		$content = parent::renderDataCellContent($model, $key, $index);
		return str_replace('fas fa-', 'fa fa-', $content);
	}

}
