<?php
/**
 * @link 
 * @copyright 
 * @license 
 */

namespace santilin\Churros;

/**
 * ProgrammerException represents an exception caused by an error in the programmer's code
 *
 * @author santilin <software@noviolento.es>
 * @since 1.0
 */
class SaveModelException extends \Exception
{

	public function __construct($model, $errorInfo = [], $code = 0, \Exception $previous = null)
	{
		$this->model = $model;
        parent::__construct("Error saving " . (string)$model, $errorInfo, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'SaveModel';
    }

    /**
     * @return string readable representation of exception
     */
    public function __toString()
    {
        return parent::__toString() . PHP_EOL
        . 'Error message:' . PHP_EOL . print_r($this->model->getErrors(), true)
        . 'Additional info:' . PHP_EOL . print_r($this->errorInfo, true);
    }
}
