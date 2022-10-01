<?php
/**
 * @link
 * @copyright
 * @license
 */

namespace santilin\churros\exceptions;
use Yii;

/**
 * ProgrammerException represents an exception caused by an error in the programmer's code
 *
 * @author santilin <software@noviolento.es>
 * @since 1.0
 */
class DupModelException extends \yii\web\HttpException
{

	public function __construct($model, $code = 0, \Exception $previous = null)
	{
		$this->model = $model;
        parent::__construct(400, $model->t('churros', "Duplicated record saving {title}\n")
				. print_r($model->getErrors(),true),
			$code, $previous);
    }

    public function getErrors()
    {
		return print_r($this->model->getErrors());
	}

}
