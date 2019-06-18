<?php
/**
 * @link 
 * @copyright 
 * @license 
 */

namespace santilin\churros;

/**
 * DataException represents an error in the user data
 *
 * @author santilin <software@noviolento.es>
 * @since 1.0
 */
class DataException extends \Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Inconsistent data';
    }
}


