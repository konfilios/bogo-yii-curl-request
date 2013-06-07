<?php
/**
 * Multi-call execution.
 *
 * @since 2.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
abstract class CBHttpMultiCall
{
	/**
	 * List of calls to be executed.
	 *
	 * @var CBHttpCall[]
	 */
	private $calls = array();

	/**
	 * Error code.
	 *
	 * @var integer
	 */
	protected $errorCode = 0;

	/**
	 * Microsecond stamp of log initialization (roughly equal to request execution).
	 *
	 * @var float
	 */
	private $startMicroStamp = 0.0;

	/**
	 * Execution time in seconds.
	 *
	 * @var integer
	 */
	private $executionSeconds = 0.0;

	/**
	 * Start the execution timer.
	 */
	protected function startTimer()
	{
		// Rough execution time
		$this->startMicroStamp = microtime(true);
	}

	/**
	 * Stop the execution timer.
	 */
	protected function stopTimer()
	{
		// Measure how many seconds it took to execute
		$this->executionSeconds = microtime(true) - $this->startMicroStamp;
	}

	/**
	 * Execution duration in seconds.
	 *
	 * @return float
	 */
	public function getExecutionSeconds()
	{
		return $this->executionSeconds;
	}

	/**
	 * Call start stamp.
	 *
	 * @return float
	 */
	public function getStartMicroStamp()
	{
		return $this->startMicroStamp;
	}

	/**
	 * Error code.
	 *
	 * @return integer
	 */
	public function getErrorCode()
	{
		return $this->errorCode;
	}

	/**
	 * Set a call with a given key.
	 *
	 * @param string $key
	 * @param CBHttpCall $call
	 */
	protected function setCall($key, CBHttpCall $call = null)
	{
		if ($call === null) {
			if (isset($this->calls[$key])) {
				unset($this->calls[$key]);
			}
		} else {
			$this->calls[$key] = $call;
		}
	}

	/**
	 * List of calls to be executed.
	 *
	 * @return CBHttpCall[]
	 */
	public function getCalls()
	{
		return $this->calls;
	}

	/**
	 * Retrieve response messages of all calls.
	 *
	 * @return CBHttpMessageResponse[]
	 */
	public function getResponseMessages()
	{
		$responseMessages = array();
		foreach ($this->calls as $key=>$call) {
			/* @var $call CBHttpCall */
			$responseMessages[$key] = $call->getResponseMessage();
		}
		return $responseMessages;
	}

	/**
	 * Execute all calls.
	 *
	 * @return CBHttpMultiCall
	 */
	abstract public function exec();
}