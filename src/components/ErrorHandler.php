<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace santilin\churros\components;

use santilin\churros\exceptions\ModuleUserException;

class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * Renders the exception.
     * @param \Throwable $exception the exception to be rendered.
     */
    protected function renderException($exception)
    {
		if( $exception instanceof ModuleUserException && !empty($exception->module) ) {
			$this->errorAction = $exception->module . '/' . $this->errorAction;
		}
		return parent::renderException($exception);
	}


	/**
     * Handles fatal PHP errors.
     */
    public function handleFatalError()
    {
        /// @todo solo si el error es out of memory
        unset($_SESSION['GridPageSize']);
        return parent::handleFatalError();
    }
}
