<?php

namespace santilin\churros\models;
use santilin\churros\helpers\AppHelper;

Trait ModelSuccessesTrait
{
	/**
	 * @var array saving success messages (attribute name => array of messages)
	 */
	private $_successes = null;

	/**
	 * Returns a value indicating whether there is any success after saving the model
	 * @return bool whether there is any success.
	 */
	public function hasSuccesses()
	{
		return !empty($this->_successes);
	}

	/**
	 * Returns the successs
	 * @return array successes for the model. Empty array is returned if no success.
	 * See [[getSuccesses()]] for detailed description.
	 *
	 * @see getFirstSuccess()
	 */
	public function getSuccesses()
	{
		return $this->_successes === null ? [] : $this->_successes;
	}

	/**
	 * Returns the first success
	 * @return string|null the success message. Null is returned if no success.
	 * @see getSuccesses()
	 * @see getFirstSuccesses()
	 */
	public function getFirstSuccess()
	{
		return count($this->_successes) ? reset($this->_successes) : null;
	}

	/**
	 * Adds a new success to the specified attribute.
	 * @param string $success new success message
	 */
	public function addSuccess(string $success = '')
	{
		$this->_successes[] = $success;
	}

	/**
	 * Adds a list of successs.
	 * @param array $items a list of successs. * The array values should be success messages.
	 * You may use the result of [[getSuccesses()]] as the value for this parameter.
	 */
	public function addSuccesses(array $items)
	{
		$this->_successes += $items;
	}

	public function addSuccessesFrom(ModelInfoTrait $model)
	{
		$this->addSuccesses($model->getSuccesses);
	}

	/**
	 * Removes all successes
	 */
	public function clearSuccesses($attribute = null)
	{
		$this->_successes = null;
	}

	public function successMessage(string $module, string $controller, string $action)
	{
		return 	$this->t('The action {action} on {la} {title} <a href="{model_link}">{record_medium}</a> has been successful.',  [ 'action' => AppHelper::modelize($action) ]);
	}

}


