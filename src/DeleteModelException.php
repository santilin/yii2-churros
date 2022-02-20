<?php
/**
 * @link
 * @copyright
 * @license
 */

namespace santilin\churros;
use Yii;

/**
 * ProgrammerException represents an exception caused by an error in the programmer's code
 *
 * @author santilin <software@noviolento.es>
 * @since 1.0
 */
class DeleteModelException extends \yii\web\HttpException
{
	public function __construct($model, \Exception $previous = null)
	{
		$this->model = $model;
		$message = $model->t('churros', 'Error deleting {la} {title} {record}');
		if( $previous->getCode() == 23000 ) {
			$message .= ":\n{Esta} {title} se usa en algún otro fichero.";
		}
        parent::__construct(400, $model-> t('churros', $message),
			$previous->getCode(), $previous);
    }

    public function getErrors()
    {
		return print_r($this->model->getErrors());
	}

}

