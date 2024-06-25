<?php

namespace santilin\churros\models;

Trait ModelWarningsTrait
{
	/**
	 * @var array validation warnings (attribute name => array of warnings)
	 */
	private $_warnings = null;

	/**
	 * Returns a value indicating whether there is any validation warning.
	 * @param string|null $attribute attribute name. Use null to check all attributes.
	 * @return bool whether there is any warning.
	 */
	public function hasWarnings($attribute = null)
	{
		return $attribute === null ? !empty($this->_warnings) : isset($this->_warnings[$attribute]);
	}

	/**
	 * Returns the warnings for all attributes or a single attribute.
	 * @param string|null $attribute attribute name. Use null to retrieve warnings for all attributes.
	 * @return array warnings for all attributes or the specified attribute. Empty array is returned if no warning.
	 * See [[getWarnings()]] for detailed description.
	 * Note that when returning warnings for all attributes, the result is a two-dimensional array, like the following:
	 *
	 * ```php
	 * [
	 *     'username' => [
	 *         'Username is required.',
	 *         'Username must contain only word characters.',
	 *     ],
	 *     'email' => [
	 *         'Email address is invalid.',
	 *     ]
	 * ]
	 * ```
	 *
	 * @see getFirstWarnings()
	 * @see getFirstWarning()
	 */
	public function getWarnings($attribute = null)
	{
		if ($attribute === null) {
			return $this->_warnings === null ? [] : $this->_warnings;
		}

		return isset($this->_warnings[$attribute]) ? $this->_warnings[$attribute] : [];
	}

	/**
	 * Returns the first warning of every attribute in the model.
	 * @return array the first warnings. The array keys are the attribute names, and the array
	 * values are the corresponding warning messages. An empty array will be returned if there is no warning.
	 * @see getWarnings()
	 * @see getFirstWarning()
	 */
	public function getFirstWarnings()
	{
		if (empty($this->_warnings)) {
			return [];
		}

		$warnings = [];
		foreach ($this->_warnings as $name => $es) {
			if (!empty($es)) {
				$warnings[$name] = reset($es);
			}
		}

		return $warnings;
	}

	/**
	 * Returns the first warning of the specified attribute.
	 * @param string $attribute attribute name.
	 * @return string|null the warning message. Null is returned if no warning.
	 * @see getWarnings()
	 * @see getFirstWarnings()
	 */
	public function getFirstWarning($attribute)
	{
		return isset($this->_warnings[$attribute]) ? reset($this->_warnings[$attribute]) : null;
	}

	/**
	 * Returns the warnings for all attributes as a one-dimensional array.
	 * @param bool $showAllWarnings boolean, if set to true every warning message for each attribute will be shown otherwise
	 * only the first warning message for each attribute will be shown.
	 * @return array warnings for all attributes as a one-dimensional array. Empty array is returned if no warning.
	 * @see getWarnings()
	 * @see getFirstWarnings()
	 * @since 2.0.14
	 */
	public function getWarningSummary($showAllWarnings)
	{
		$lines = [];
		$warnings = $showAllWarnings ? $this->getWarnings() : $this->getFirstWarnings();
		foreach ($warnings as $es) {
			$lines = array_merge($lines, (array)$es);
		}
		return $lines;
	}

	/**
	 * Adds a new warning to the specified attribute.
	 * @param string $attribute attribute name
	 * @param string $warning new warning message
	 */
	public function addWarning($attribute, $warning = '')
	{
		$this->_warnings[$attribute][] = $warning;
	}

	/**
	 * Adds a list of warnings.
	 * @param array $items a list of warnings. The array keys must be attribute names.
	 * The array values should be warning messages. If an attribute has multiple warnings,
	 * these warnings must be given in terms of an array.
	 * You may use the result of [[getWarnings()]] as the value for this parameter.
	 * @since 2.0.2
	 */
	public function addWarnings(array $items)
	{
		foreach ($items as $attribute => $warnings) {
			if (is_array($warnings)) {
				foreach ($warnings as $warning) {
					$this->addWarning($attribute, $warning);
				}
			} else {
				$this->addWarning($attribute, $warnings);
			}
		}
	}

	public function addWarningsFrom(ActiveRecord $model, $key = null)
	{
		if ($key !== null) {
			$key = $key . '.';
		}
		foreach ($model->getWarnings() as $k => $warning) {
			foreach ($warning as $warn_msg ) {
				$this->addWarning($key . $k, $warn_msg);
			}
		}
	}


	/**
	 * Removes warnings for all attributes or a single attribute.
	 * @param string|null $attribute attribute name. Use null to remove warnings for all attributes.
	 */
	public function clearWarnings($attribute = null)
	{
		if ($attribute === null) {
			$this->_warnings = [];
		} else {
			unset($this->_warnings[$attribute]);
		}
	}

	public function getOneWarning():string
	{
		$warnings = $this->getFirstWarnings(false);
		if( count($warnings) ) {
			return reset($warnings);
		} else {
			return '';
		}
	}



}


