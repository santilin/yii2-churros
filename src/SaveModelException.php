<?php
/**
 * @link
 * @copyright
 * @license
 */

namespace santilin\Churros;
use Yii;

/**
 * ProgrammerException represents an exception caused by an error in the programmer's code
 *
 * @author santilin <software@noviolento.es>
 * @since 1.0
 */
class SaveModelException extends \yii\web\HttpException
{

	public function __construct($model, $code = 0, \Exception $previous = null)
	{
		$this->model = $model;
        parent::__construct(400, $model->t('churros', "Error saving the {title}"), $code, $previous);
    }

    public function getErrors()
    {
		return print_r($this->model->getErrors());
	}

}
