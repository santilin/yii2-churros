<?php

namespace santilin\churros\models;
use santilin\churros\helpers\AppHelper;

Trait ModelTracesTrait
{
	/**
	 * @var array saving trace messages (attribute name => array of messages)
	 */
	private ?array $_traces = null;

	/**
	 * Returns a value indicating whether there is any trace after saving the model
	 * @return bool whether there is any trace.
	 */
	public function hasTraces()
	{
		return !empty($this->_traces);
	}

	/**
	 * Returns the traces
	 * @return array traces for the model. Empty array is returned if no trace.
	 * See [[getTraces()]] for detailed description.
	 *
	 * @see getFirstTrace()
	 */
	public function getTraces()
	{
		return $this->_traces === null ? [] : $this->_traces;
	}

	/**
	 * Returns the first trace
	 * @return string|null the trace message. Null is returned if no trace.
	 * @see getTraces()
	 * @see getFirstTraces()
	 */
	public function getFirstTrace()
	{
		return count($this->_traces) ? reset($this->_traces) : null;
	}

	/**
	 * Adds a new trace to the specified attribute.
	 * @param string $trace new trace message
	 */
	public function addTrace(string $trace = '')
	{
		$this->_traces[] = $trace;
	}

	/**
	 * Adds a list of traces.
	 * @param array $items a list of traces. * The array values should be trace messages.
	 * You may use the result of [[getTraces()]] as the value for this parameter.
	 */
	public function addTraces(array $items)
	{
		$this->_traces += $items;
	}

	public function addTracesFrom(ModelTracesTrait $model)
	{
		// $this->addTraces($model->getTraces);
	}

	/**
	 * Returns the traces for all attributes as a one-dimensional array.
	 * @param bool $showAllWarnings boolean, if set to true every trace message for each attribute will be shown otherwise
	 * only the first trace message for each attribute will be shown.
	 * @return array traces for all attributes as a one-dimensional array. Empty array is returned if no trace.
	 * @see getWarnings()
	 * @see getFirstWarnings()
	 * @since 2.0.14
	 */
	public function getTracesSummary($showAllTraces = true)
	{
		$lines = [];
		$traces = $showAllTraces ? $this->getTraces() : $this->getFirstTrace();
		foreach ($traces as $es) {
			$lines = array_merge($lines, (array)$es);
		}
		return $lines;
	}

	/**
	 * Removes all traces
	 */
	public function clearTraces($attribute = null)
	{
		$this->_traces = null;
	}

	public function traceMessage(string $module, string $controller, string $action)
	{
		return 	$this->t('The action {action} on {la} {title} <a href="{record_url}">{record_medium}</a> has been successful.',  [ 'action' => AppHelper::modelize($action) ]);
	}

}


