<?php
/**
 * Http call executor.
 *
 * <p>Coordinates execution of http calls utilizing lower-level components such as CBHttpCall
 * and CBHttpMultiCall.</p>
 *
 *
 * <h2>Sample usage:</h2>
 * <pre>
 * // Instantiate executor
 * $executor = new CBHttpCallExecutor;
 *
 * // Go through the list of calls you want to execute
 * foreach ($pendingCalls as $pendingCall) {
 *
 *	// Prepare $pendingCall
 *
 *	// Submit $pendingCall for execution
 *	$completedCalls = $executor->submit($pendingCall);
 *
 *	// Parse $completedCalls
 *
 * }
 *
 * // Make sure all submited calls have been invoked
 * $completedCalls = $executor->invokeAll();
 *
 * // Parse last $completedCalls
 *
 * </pre>
 *
 * @since 2.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
abstract class CBHttpCallExecutor extends CComponent
{
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
	 * Submit call for execution.
	 *
	 * @param CBHttpCall $call Call to be executed.
	 * @return CBHttpCall[] List of completed calls.
	 */
	abstract public function submit(CBHttpCall $call);

	/**
	 * Invoke all pending submitted calls.
	 *
	 * @return CBHttpCall[] List of completed calls.
	 */
	abstract public function invokeAll();

	/**
	 * Increment call count.
	 *
	 * @param integer $inc
	 */
	protected function incrementTotalExecutedCallCount($inc)
	{
		$this->totalExecutedCallCount += $inc;
	}

	/**
	 * Increment call count.
	 *
	 * @param integer $inc
	 */
	protected function incrementTotalCallExecutionSeconds($inc)
	{
		$this->totalCallExecutionSeconds += $inc;
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
	 * Called before invokeAll runs on a non-empty call buffer.
	 *
	 * @param CEvent $event
	 */
	public function onBeforeInvokeAll(CEvent $event)
	{
		$this->raiseEvent('onBeforeInvokeAll', $event);
	}

	/**
	 * Called after invokeAll runs on a non-empty call buffer.
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
	 * @param CBHttpCallEvent $event
	 */
	public function onAfterCompleteCall(CBHttpCallEvent $event)
	{
		$this->raiseEvent('onAfterCompleteCall', $event);
	}
}
