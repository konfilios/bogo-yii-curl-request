<?php
/**
 * General purpose rest client log.
 *
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBCurlLogger
{
	// Response body was totally empty
	const ERROR_EMPTY_RESPONSE = 1;

	// Response body was malformed, i.e. it could not be completely deserialized
	// (eg. malformed json/xml)
	const ERROR_MALFORMED_RESPONSE = 2;

	// Response was non-empty and properly deserialized but it does make sense
	// This indicates an application bug from the other side
	const ERROR_UNEXPECTED_RESPONSE = 3;

	// ErrorType rules are not followed. Maybe some real exception is hidden here
	const ERROR_POSSIBLE_EXCEPTION = 4;

	// Fully custom exception
	const ERROR_CUSTOM_EXCEPTION = 5;
	const SPECIAL_SUCCESS = 0;
	const SPECIAL_INFO = 1;
	const SPECIAL_SUGGESTION = 2;
	const SPECIAL_WARNING = 3;
	const SPECIAL_ERROR = 4;

	/**
	 * Number of calls executed.
	 *
	 * @var integer
	 */
	static public $calls = 0;

	/**
	 * Time of calls executed.
	 *
	 * @var float
	 */
	static public $time = 0.0;

	/**
	 * Labels of application errors.
	 *
	 * @var string[]
	 */
	protected $applicationErrorTexts = array(
		self::ERROR_EMPTY_RESPONSE => 'Empty Response',
		self::ERROR_MALFORMED_RESPONSE => 'Malformed Response',
		self::ERROR_UNEXPECTED_RESPONSE => 'Unexpected Response',
		self::ERROR_POSSIBLE_EXCEPTION => 'Possible Exception',
		self::ERROR_CUSTOM_EXCEPTION => 'Custom Exception'
	);

	/**
	 * Lables of special return codes.
	 *
	 * @var string[]
	 */
	protected $specialTexts = array(
		self::SPECIAL_SUCCESS => 'Success',
		self::SPECIAL_INFO => 'Info',
		self::SPECIAL_SUGGESTION => 'Suggestion',
		self::SPECIAL_WARNING => 'Warning',
		self::SPECIAL_ERROR => 'Error'
	);

	/**
	 * CURL Error. Defaults to curl success (CURLE_OK = 0).
	 *
	 * @var integer
	 */
	protected $curlErrorCode = 0;

	/**
	 * Application fatal error family. Defaults to undefined.
	 *
	 * @var integer
	 */
	public $applicationErrorCode = null;

	/**
	 * Error string (null means success).
	 *
	 * @var boolean
	 */
	public $errorText = null;

	/**
	 * Microsecond stamp of log initialization (roughly equal to request execution).
	 *
	 * @var float 
	 */
	public $microStart = null;

	/**
	 * Execution time in seconds.
	 *
	 * @var integer
	 */
	public $executionSeconds = null;

	/**
	 * Verbose CURL info about the request.
	 *
	 * @var string
	 */
	public $curlVerboseInfo = null;

	/**
	 * Request message.
	 *
	 * @var CBHttpMessageRequest
	 */
	public $requestMessage = null;
	/**
	 * Response message.
	 *
	 * @var CBHttpMessageResponse
	 */
	public $responseMessage = null;

	/**
	 * Keep request message.
	 *
	 * @param CBHttpMessageRequest $message
	 */
	public function setRequestInfo(CBHttpMessageRequest $message)
	{
		$this->requestMessage = $message;
		$this->startTimer();
	}

	/**
	 * Keep response message.
	 *
	 * @param CBHttpMessageResponse $message
	 */
	public function setResponseInfo(CBHttpMessageResponse $message)
	{
		$this->stopTimer();
		$this->responseMessage = $message;
	}

	private function startTimer()
	{
		// Keep statistics
		self::$calls++;

		// Rough execution time
		$this->microStart = microtime(true);
	}

	private function stopTimer()
	{
		// Measure how many seconds it took to execute
		$this->executionSeconds = microtime(true) - $this->microStart;

		// Keep statistics
		self::$time += $this->executionSeconds;
	}

	/**
	 * Set curl verbose info
	 * @param string $curlVerboseInfo
	 */
	public function setCurlVerboseInfo($curlVerboseInfo)
	{
		$this->curlVerboseInfo = $curlVerboseInfo;
	}

	/**
	 * An application error occured.
	 *
	 * @param integer $errorCode   Error code.
	 * @param string  $errorString Error description.
	 *
	 * @return void
	 */
	public function onError($errorCode, $errorString)
	{
		$this->applicationErrorCode = $errorCode;
		$this->errorText = $errorString;
	}

	/**
	 * Call finally succeeded.
	 *
	 * @param mixed   $responseData Data returned from call.
	 * @param integer $specialCode  Special code of call outcome.
	 *
	 * @return void
	 */
	public function onSuccess($responseData, $specialCode = self::SPECIAL_SUCCESS)
	{
	}

	/**
	 * Set curl failure details.
	 *
	 * @param integer $errorNumber Error code/number.
	 * @param string  $errorString Error description.
	 *
	 * @return void
	 */
	public function setCurlError($errorNumber, $errorString)
	{
		$this->curlErrorCode = $errorNumber;
		$this->errorText = $errorString;
		$this->stopTimer();
	}
}
