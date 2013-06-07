<?php
/**
 * Base HTTP Call.
 *
 * @since 2.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
abstract class CBHttpCall
{
	/**
	 * Call created but not executed.
	 */
	const STATE_CREATED = 1;

	/**
	 * Call is currently being executed.
	 */
	const STATE_RUNNING = 2;

	/**
	 * Execution completed.
	 */
	const STATE_COMPLETED = 3;

	/**
	 * Text representation of states.
	 *
	 * @var string[]
	 */
	static public $stateTitles = array(
		self::STATE_CREATED => 'Created',
		self::STATE_RUNNING => 'Running',
		self::STATE_COMPLETED => 'Completed',
	);

	/**
	 * Total number of calls executed.
	 *
	 * @var integer
	 */
	static public $totalCallCount = 0;

	/**
	 * Total time consumed in call execution.
	 *
	 * @var float
	 */
	static public $totalExecutionSeconds = 0.0;

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
	 * Timeout in seconds.
	 *
	 * @var float
	 */
	protected $timeoutSeconds = 1.0;

	/**
	 * Current state.
	 *
	 * @var integer
	 */
	private $state = self::STATE_CREATED;

	/**
	 * Perform extra debugging tasks.
	 *
	 * @var boolean
	 */
	private $inDebugMode = false;

	/**
	 * Code of execution error.
	 *
	 * @var integer
	 */
	protected $errorCode = 0;

	/**
	 * Message of execution error.
	 *
	 * @var string
	 */
	protected $errorMessage = '';

	/**
	 * Request message.
	 *
	 * @var CBHttpMessageRequest
	 */
	protected $requestMessage = null;

	/**
	 * Response message.
	 *
	 * @var CBHttpMessageResponse
	 */
	protected $responseMessage = null;

	/**
	 * Code of execution error.
	 *
	 * @return integer
	 */
	public function getErrorCode()
	{
		return $this->errorCode;
	}

	/**
	 * Message of execution error.
	 *
	 * @var string
	 */
	public function getErrorMessage()
	{
		return $this->errorMessage;
	}

	/**
	 * Perform extra debugging tasks.
	 *
	 * @return boolean
	 */
	public function getInDebugMode()
	{
		return $this->inDebugMode;
	}

	/**
	 * Perform extra debugging tasks.
	 *
	 * @param boolean $inDebugMode
	 * @return CBHttpCall
	 */
	public function setInDebugMode($inDebugMode)
	{
		$this->inDebugMode = $inDebugMode;

		return $this;
	}

	/**
	 * Request message.
	 *
	 * @return CBHttpMessageRequest
	 */
	public function getRequestMessage()
	{
		return $this->requestMessage;
	}

	/**
	 * Response message.
	 *
	 * @return CBHttpMessageResponse
	 */
	public function getResponseMessage()
	{
		return $this->responseMessage;
	}

	/**
	 * Timeout in seconds.
	 *
	 * @return float
	 */
	public function getTimeoutSeconds()
	{
		return $this->timeoutSeconds;
	}

	/**
	 * Timeout in seconds.
	 *
	 * @param float $timeoutSeconds
	 * @return CBHttpCall
	 */
	public function setTimeoutSeconds($timeoutSeconds)
	{
		$this->timeoutSeconds = $timeoutSeconds;

		return $this;
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
	 * Start the execution timer.
	 */
	private function startTimer()
	{
		// Keep statistics
		self::$totalCallCount++;

		// Rough execution time
		$this->startMicroStamp = microtime(true);
	}

	/**
	 * Stop the execution timer.
	 */
	private function stopTimer()
	{
		// Measure how many seconds it took to execute
		$this->executionSeconds = microtime(true) - $this->startMicroStamp;

		// Keep statistics
		self::$totalExecutionSeconds += $this->executionSeconds;
	}

	/**
	 * Change internal state of call.
	 *
	 * @param integer $newState
	 */
	protected function setState($newState)
	{
		switch ($newState) {
		case self::STATE_RUNNING:
			if ($this->state != self::STATE_CREATED) {
				throw new Exception('Cannot get to '.self::$stateTitles[$newState].' from '.self::$stateTitles[$this->state]);
			}
			$this->startTimer();
			break;

		case self::STATE_COMPLETED:
			if ($this->state != self::STATE_RUNNING) {
				throw new Exception('Cannot get to '.self::$stateTitles[$newState].' from '.self::$stateTitles[$this->state]);
			}
			$this->stopTimer();
			break;
		}

		// Everythin ok, update state
		$this->state = $newState;
	}

	/**
	 * Current call state.
	 *
	 * @return integer
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * Construct with request and response message.
	 *
	 * @param CBHttpMessageRequest $requestMessage
	 * @param CBHttpMessageResponse $responseMessage
	 */
	public function __construct(CBHttpMessageRequest $requestMessage, CBHttpMessageResponse $responseMessage = null)
	{
		$this->requestMessage = $requestMessage;
		$this->responseMessage = $responseMessage ?: new CBHttpMessageResponse();
	}

	/**
	 * Instantiate a cURL HTTP call.
	 *
	 * @param CBHttpMessageRequest|string $requestMessageOrVerb Request message or request verb
	 * @param string $requestUri
	 * @return CBHttpCall
	 */
	static public function create($requestMessageOrVerb, $requestUri = null)
	{
		// Determine request message
		if ($requestMessageOrVerb instanceof CBHttpMessageRequest) {
			$requestMessage = $requestMessageOrVerb;
		} else {
			$requestMessage = CBHttpMessageRequest::create($requestMessageOrVerb, $requestUri);
		}

		// Instantiate call object
		$httpCallClassName = get_called_class();
		return new $httpCallClassName($requestMessage);
	}

	/**
	 * Executes request message and returns response message.
	 *
	 * @return CBHttpMessageResponse
	 */
	abstract public function exec();

	/**
	 * Returns extra debug info about call.
	 *
	 * The return value is vendor specific, but you should only get a result if $inDebugMode
	 * is turned on.
	 *
	 * @return mixed
	 */
	abstract public function getDebugInfo();
}
