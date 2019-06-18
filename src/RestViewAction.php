<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros;

use Yii;
use yii\rest\ViewAction;

/**
 * ViewAction implements the API endpoint for returning the detailed information about a model.
 *
 * For more details and usage information on ViewAction, see the [guide article on rest controllers](guide:rest-controllers).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class RestViewAction extends ViewAction
{
    /**
     * Displays a model.
     * @param string $id the primary key of the model.
     * @return \yii\db\ActiveRecordInterface the model being displayed
     */
    public function run($id)
    {
		if ($id == 0 ) {
			/* @var $model \yii\db\ActiveRecord */
			$model = new $this->modelClass();
			$model->setDefaultValues();
			return $model;
		} else {
			return parent::run($id);
		}
    }
}
