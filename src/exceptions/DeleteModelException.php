<?php
/**
 * @link
 * @copyright
 * @license
 */

namespace santilin\churros\exceptions;
use Yii;

/**
 * DeleteModelException
 *
 * @author santilin <software@noviolento.es>
 * @since 1.0
 */
class DeleteModelException extends \yii\web\HttpException
{
	public function __construct($model, \Exception $previous = null)
	{
		$this->model = $model;
		$message = $model->t('churros', 'Error deleting {la} {title} {record_medium}');
		if( $previous->getCode() == 23000 ) {
			$message .= ":\n{Esta} {title} is used in other files";
		}
        parent::__construct(400, $model-> t('churros', $message),
			$previous->getCode(), $previous);
    }

    public function getErrors()
    {
		return print_r($this->model->getErrors());
	}

}

