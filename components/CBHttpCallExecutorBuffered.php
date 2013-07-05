<?php
/**
 * Buffered call executor.
 *
 * <h2>Usage:</h2>
 * <ul>
 * <li>Create a buffered executor with a given buffer size.</li>
 * <li>Start adding calls to be executed using submit().</li>
 * <li>Whenever enough calls have been accumulated, submit() internally calls invokeAll()
 * to execute the calls using a CBHttpMultiCall.</li>
 * </ul>
 *
 * You may call invokeAll() manually to make sure no calls have been submitted but not executed.
 *
 * @since 2.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBHttpCallExecutorBuffered extends CComponent
{
	/**
	 * Number of calls to buffer before invokeAll().
	 *
	 * @var integer
	 */
	private $bufferSize = 0;

	/**
	 * Buffer of submit()ed calls.
	 *
	 * @var CBHttpCallCurl[]
	 */
	private $bufferedCalls = array();

	/**
	 * Total number of calls executed.
	 *
	 * @var integer
	 */
	private $totalExecutedCallCount = 0;

	/**
	 * Total time spent in call execution (in seconds).
	 *
	 * @var float
	 */
	private $totalCallExecutionSeconds = 0.0;

	/**
	 * Construct new buffered executor.
	 *
	 * @param integer $bufferSize
	 */
	public function __construct($bufferSize)
	{
		$this->bufferSize = $bufferSize;
	}

	/**
	 * Submit call for execution.
	 *
	 * If capacity of buffer has been reached, invokeAll() is called.
	 *
	 * @param CBHttpCallCurl $call
	 * @return CBHttpCall[] List of executed calls.
	 */
	public function submit(CBHttpCallCurl $call)
	{
		// Create call and push to queue
		$this->bufferedCalls[] = $call;

		if (count($this->bufferedCalls) >= $this->bufferSize) {
			return $this->invokeAll();
		} else {
			return array();
		}
	}

	/**
	 * Invoke all pending submitted calls.
	 *
	 * Buffered calls are executed concurrently using a CBHttpMultiCallCurlParallel.
	 *
	 * @return CBHttpCall[] List of executed calls.
	 */
	public function invokeAll()
	{
		if (empty($this->bufferedCalls)) {
			return array();
		}

		if ($this->hasEventHandler('onBeforeInvokeAll')) {
			$this->onBeforeInvokeAll(new CEvent($this));
		}

		// Create multi-call
		$multiCall = new CBHttpMultiCallCurlParallel($this->bufferedCalls);
		/* @var $multiCall CBHttpMultiCallCurlParallel */

		$executedCalls = $multiCall->exec()->getCalls();

		if ($this->hasEventHandler('onCallCompleted')) {
			$onCallCompletedEvent = new CEvent($this);
			$onCallCompletedEvent->params = array();

			foreach ($executedCalls as $call) {
				/* @var $call CBHttpCall */

				$onCallCompletedEvent->params['call'] = $call;

				$this->onCallCompleted($onCallCompletedEvent);
			}
		}

		// Keep statistics
		$this->totalExecutedCallCount += count($this->bufferedCalls);
		$this->totalCallExecutionSeconds += $multiCall->getExecutionSeconds();

		// Reset queue
		$this->bufferedCalls = array();

		if ($this->hasEventHandler('onAfterInvokeAll')) {
			$this->onAfterInvokeAll(new CEvent($this));
		}

		return $executedCalls;
	}

	/**
	 * Mean number of calls executed per second.
	 *
	 * @return float
	 */
	public function getMeanThroughput()
	{
		return $this->totalCallExecutionSeconds ? $this->totalExecutedCallCount / $this->totalCallExecutionSeconds : 0;
	}

	/**
	 * Total number of calls executed.
	 *
	 * @return integer
	 */
	public function getTotalExecutedCallCount()
	{
		return $this->totalExecutedCallCount;
	}

	/**
	 * Total time spent in call execution (in seconds).
	 *
	 * @return float
	 */
	public function getTotalCallExecutionSeconds()
	{
		return $this->totalCallExecutionSeconds;
	}

	/**
	 * Called before a non-empty queue is flushed.
	 *
	 * @param CEvent $event
	 */
	public function onBeforeInvokeAll(CEvent $event)
	{
		$this->raiseEvent('onBeforeInvokeAll', $event);
	}

	/**
	 * Called after a non-empty queue is flushed.
	 *
	 * @param CEvent $event
	 */
	public function onAfterInvokeAll(CEvent $event)
	{
		$this->raiseEvent('onAfterInvokeAll', $event);
	}

	/**
	 * Called after an individual call is completed.
	 *
	 * Event parameters include the 'responseMessage' and the corresponding 'notification'.
	 *
	 * @param CEvent $event
	 */
	public function onCallCompleted(CEvent $event)
	{
		$this->raiseEvent('onCallCompleted', $event);
	}
}
