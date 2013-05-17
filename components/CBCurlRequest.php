<?php
/**
 * Curl request wrapper.
 *
 * @link http://www.gen-x-design.com/archives/making-restful-requests-in-php/
 *
 * @since 1.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBCurlRequest
{
	const CONTENT_JSON = 1;
	const CONTENT_XML = 2;
	const RETURN_RAW = 1;
	const RETURN_OBJECT = 2;
	const RETURN_ASSOC = 3;

	/**
	 * Available content types.
	 *
	 * @var string[]
	 */
	protected static $contentTypes = array(
		self::CONTENT_JSON => 'application/json',
		self::CONTENT_XML => 'application/xhtml+xml'
	);

	/**
	 * Logger (optional).
	 *
	 * @var CBCurlLogger
	 */
	protected $logger = null;

	/**
	 * Files to be sent.
	 *
	 * @var array
	 */
	private $requestFiles = array();

	/**
	 * Get parameters. Used with all verbs.
	 *
	 * @var array
	 */
	protected $requestGetParams = array();

	/**
	 * Post parameters. Used only for POST requests.
	 *
	 * @var array
	 */
	protected $requestPostParams = array();

	/**
	 * HTTP authorization.
	 *
	 * @var string
	 */
	protected $auth = null;

	/**
	 * Timeout in seconds.
	 *
	 * @var integer
	 */
	public $timeoutSeconds = 15;

	/**
	 * Encoding of data to be sent.
	 *
	 * @var string
	 */
	protected $requestEncoding = 'gzip';

	/**
	 * Last URL executed.
	 *
	 * @var string
	 */
	protected $lastUrl = '';

	/**
	 * Last http verb used (get, post, put, etc).
	 *
	 * @var string
	 */
	protected $lastVerb = '';

	/**
	 * Incoming response details.
	 *
	 * @var array
	 */
	private $responseDetails = array();

	/**
	 * Verbose CURL info about the request.
	 *
	 * @var string
	 */
	protected $curlVerboseInfo = null;

	/**
	 * Request message.
	 *
	 * @var CBCurlMessage
	 */
	protected $requestMessage = null;

	/**
	 * Response message.
	 *
	 * @var CBCurlMessage
	 */
	protected $responseMessage = null;
	/**
	 * Set outgoing http data encoding
	 *
	 * @param string $encoding
	 * @return Rest
	 */
	/* 	public function setEncoding($encoding)
	  {
	  $this->encoding = $encoding;

	  return $this;
	  }
	 */
	/**
	 * Set http request timeout (in seconds)
	 *
	 * @param int $timeout
	 * @return Rest
	 */
	/* 	public function setTimeout($timeout)
	  {
	  $this->timeout = $timeout;

	  return $this;
	  }
	 */
	/**
	 * Set HTTP auth credentials
	 *
	 * @param string $user
	 * @param string $pass
	 * @return Rest
	 */
	/* 	public function setAuth($user = null, $pass = null)
	  {
	  if (empty($user) && empty($pass)) {
	  $this->auth = '';
	  } else {
	  $this->auth = $user.":".$pass;
	  }

	  return $this;
	  }
	 */

	public function __construct()
	{
		$this->requestMessage = new CBCurlMessage();
		$this->responseMessage = new CBCurlMessage();
	}

	/**
	 * Reset all request and response data.
	 *
	 * @return Rest
	 */
	public function reset()
	{
		$this->requestPostParams = array();
		$this->requestGetParams = array();
		$this->requestFiles = array();
		$this->responseDetails = array();
		$this->requestMessage->reset();
//		$this->responseMessage->reset();

		return $this;
	}

	/**
	 * Add a request cookie.
	 *
	 * @param string $cookieName Name of request header.
	 * @param string $cookieValue Value of request header.
	 *
	 * @return Rest
	 */
	public function setRequestCookie($cookieName, $cookieValue)
	{
		$this->requestMessage->setCookie($cookieName, $cookieValue);

		return $this;
	}

	/**
	 * Add a request header.
	 *
	 * @param string $field Name of request header.
	 * @param string $value Value of request header.
	 *
	 * @return Rest
	 */
	public function setRequestHeader($field, $value = null)
	{
		$this->requestMessage->setHeader($field, $value);

		return $this;
	}

	/**
	 * Queue $filename for uploading as $field.
	 *
	 * @param string $field    Name of file in the request.
	 * @param string $filename Filename to upload or null to unset.
	 *
	 * @return Rest
	 */
	public function setRequestFile($field, $filename = null)
	{
		$this->requestFiles[$field] = "@".realpath($filename);

		return $this;
	}

	/**
	 * Set value for a POST field.
	 *
	 * @param string $field Parameter name.
	 * @param string $value New value or null to unset.
	 *
	 * @return Rest
	 */
	public function setRequestPostParam($field, $value = null)
	{
		if (is_null($value)) {
			// Unsetting
			if (isset($this->requestGetParams[$field])) {
				unset($this->requestGetParams[$field]);
			}
		} else {
			$this->requestPostParams[$field] = (is_array($value) || is_object($value)) ?
					json_encode($value) : $value;
		}

		return $this;
	}

	/**
	 * Set value for a GET field.
	 *
	 * @param string $field Parameter name.
	 * @param string $value New value or null to unset.
	 *
	 * @return Rest
	 */
	public function setRequestGetParam($field, $value = null)
	{
		if (is_null($value)) {
			// Unsetting
			if (isset($this->requestGetParams[$field])) {
				unset($this->requestGetParams[$field]);
			}
		} else {
			// Setting new value
			$this->requestGetParams[$field] = (is_array($value) || is_object($value)) ?
					json_encode($value) : $value;
		}

		return $this;
	}

	/**
	 * Retrieve a request get parameter (if already set).
	 *
	 * @param string $field Parameter name.
	 *
	 * @return mixed Value of get parameter previously set using setGetParam() or null
	 */
	public function getRequestGetParam($field)
	{
		return isset($this->requestGetParams[$field]) ? $this->requestGetParams[$field] : null;
	}

	/**
	 * Set JSON body. If $data is in string format, it's left as is. Otherwise it's json encoded.
	 *
	 * @param mixed $data Json data.
	 *
	 * @return Rest
	 */
	public function setRequestJsonBody($data)
	{
		$this->setRequestHeader('Content-type', self::$contentTypes[self::CONTENT_JSON]);
		$this->requestMessage->rawBody = is_string($data) ? $data : json_encode($data);

		return $this;
	}

	/**
	 * Set XML body/data.
	 *
	 * @param string $data XML data.
	 *
	 * @return Rest
	 */
	public function setRequestXml($data)
	{
		$this->setRequestHeader('Content-type', self::$contentTypes[self::CONTENT_XML]);
		$this->requestMessage->rawBody = $data;

		return $this;
	}

	/**
	 * Get a field or the whole array of the response details.
	 *
	 * @param string $field Detail name.
	 *
	 * @return mixed
	 */
	public function getResponseDetail($field = '')
	{
		if (empty($this->responseDetails)) {
			return false;
		} else if (empty($field)) {
			return $this->responseDetails;
		} else {
			return $this->responseDetails[$field];
		}
	}

	/**
	 * Get a specific response header or all (if no $field is given).
	 *
	 * @param string $headerName Header name.
	 *
	 * @return mixed
	 */
	public function getResponseHeader($headerName = '')
	{
		return $this->responseMessage->getHeader($headerName);
	}

	/**
	 * Get a specific response cookie attributes or all (if no $cookieName is given).
	 *
	 * @param string $cookieName Cookie name.
	 *
	 * @return array
	 */
	public function getResponseCookieAttributes($cookieName = '')
	{
		return $this->responseMessage->getCookieAttributes($cookieName);
	}

	/**
	 * Get a specific response cookie value or all (if no $cookieName is given).
	 *
	 * @param string $cookieName Cookie name.
	 *
	 * @return mixed
	 */
	public function getResponseCookie($cookieName = '')
	{
		return $this->responseMessage->getCookie($cookieName);
	}

	/**
	 * Prepare and return body for request. Set appropriate headers.
	 *
	 * @return mixed
	 */
	protected function compileRequestBody()
	{
		if (!empty($this->requestMessage->rawBody)) {
			// Raw body
			return $this->requestMessage->rawBody;
		} else {
			// Set post body. Maybe we need files as well
			if (empty($this->requestFiles)) {
				// Content-type: application/x-www-form-urlencoded
				return http_build_query($this->requestPostParams, '', '&');
			} else {
				// Content-type: multipart/form-data
				return $this->requestPostParams + $this->requestFiles;
			}
		}
	}

	/**
	 * Issue a POST request.
	 *
	 * @param string  $url               Target url.
	 * @param integer $acceptContentType Expected response content type.
	 * @param integer $returnFormat      Type of data to be returned.
	 *
	 * @return mixed
	 */
	public function post($url, $acceptContentType = 0, $returnFormat = 0)
	{
		$this->lastVerb = 'POST';

		$ch = $this->curlInit($url, $acceptContentType);

		// It's a post request
		curl_setopt($ch, CURLOPT_POST, 1);

		// Build and set body
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->compileRequestBody());

		return $this->curlExec($ch, $acceptContentType, $returnFormat);
	}

	/**
	 * Issue a GET request.
	 *
	 * @param string  $url               Target url.
	 * @param integer $acceptContentType Expected response content type.
	 * @param integer $returnFormat      Type of data to be returned.
	 *
	 * @return mixed
	 */
	public function get($url, $acceptContentType = 0, $returnFormat = 0)
	{
		$this->lastVerb = 'GET';

		return $this->curlExec($this->curlInit($url), $acceptContentType, $returnFormat);
	}

	/**
	 * Issue a DELETE request.
	 *
	 * @param string  $url               Target url.
	 * @param integer $acceptContentType Expected response content type.
	 * @param integer $returnFormat      Type of data to be returned.
	 *
	 * @return mixed
	 */
	public function delete($url, $acceptContentType = 0, $returnFormat = 0)
	{
		$this->lastVerb = 'DELETE';

		$ch = $this->curlInit($url);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		return $this->curlExec($ch, $acceptContentType, $returnFormat);
	}

	/**
	 * Issue a PUT request.
	 *
	 * @param string  $url               Target url.
	 * @param integer $acceptContentType Expected response content type.
	 * @param integer $returnFormat      Type of data to be returned.
	 *
	 * @return mixed
	 */
	public function put($url, $acceptContentType = 0, $returnFormat = 0)
	{
		$this->lastVerb = 'PUT';

		$ch = $this->curlInit($url);

		// It's a PUT request
		curl_setopt($ch, CURLOPT_PUT, true);

		$data = $this->compileRequestBody();

		// Only string data may be used
		if (is_string($data) && !empty($data)) {
			$fh = fopen('php://temp', 'rw');
			fwrite($fh, $data);
			rewind($fh);

			curl_setopt($ch, CURLOPT_INFILE, $fh);
			curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));
		}

		return $this->curlExec($ch, $acceptContentType, $returnFormat);
	}

	/**
	 * Parse response headers.
	 *
	 * Callback function used by CURLOPT_HEADERFUNCTION.
	 *
	 * @param mixed  $ch         Curl resource.
	 * @param string $headerLine Header string containing both field name and value.
	 *
	 * @return integer Number of bytes parsed
	 */
	private function parseResponseHeader($ch, $headerLine)
	{
		$pos = strpos($headerLine, ':');

		if ($pos !== false) {
			$field = strtolower(trim(substr($headerLine, 0, $pos)));
			$value = trim(substr($headerLine, $pos + 1));
			$this->responseMessage->setHeader($field, $value);

			if (($field == 'set-cookie') && !empty($value)) {
				// Response cookie
				$rawCookieAttributes = explode(';', $value);

				$cookieName = null;
				$cookieValue = null;
				$cookieAttributes = array();
				foreach ($rawCookieAttributes as $rawCookieAttribute) {
					// Explode into parts
					$rawCookieAttributeParts = explode('=', trim($rawCookieAttribute));

					if (empty($rawCookieAttributeParts)) {
						// Nothing in there
						continue;
					}

					// Trim first
					$cookieAttributeName = trim($rawCookieAttributeParts[0]);
					if (isset($rawCookieAttributeParts[1])) {
						// Attribute-value pair, this is the value
						$cookieAttributeValue = trim($rawCookieAttributeParts[1]);
					} else {
						// Flag, its value is true
						$cookieAttributeValue = true;
					}

					if (empty($cookieAttributes)) {
						// This is the first cookie attribute, i.e. the cookieName=cookieValue pair
						$cookieName = $cookieAttributeName;
						$cookieValue = $cookieAttributeValue;
					} else {
						$cookieAttributes[$cookieAttributeName] = $cookieAttributeValue;
					}
				}

				// Save the cookie
				$this->responseMessage->setCookie($cookieName, $cookieValue, $cookieAttributes);
			}
		}

		return strlen($headerLine);
	}

	/**
	 * Parse response body.
	 *
	 * Callback function used by CURLOPT_WRITEFUNCTION.
	 *
	 * @param mixed  $ch                Curl resource.
	 * @param string $responseBodyChunk Response body chunk.
	 *
	 * @return integer Number of bytes parsed
	 */
	private function parseResponseBody($ch, $responseBodyChunk)
	{
		if ($this->responseMessage->rawBody === null) {
			$this->responseMessage->rawBody = $responseBodyChunk;
		} else {
			$this->responseMessage->rawBody .= $responseBodyChunk;
		}

		return strlen($responseBodyChunk);
	}

	/**
	 * Initialize a curl object and set common options.
	 *
	 * @param string $url Called url.
	 *
	 * @return resource
	 */
	protected function curlInit($url)
	{
		$ch = curl_init();

		// Append get parameters
		if (!empty($this->requestGetParams)) {
			$url .= '?'.http_build_query($this->requestGetParams, '', '&');
		}

		// Set basic options
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'parseResponseHeader'));
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this, 'parseResponseBody'));
		$this->lastUrl = $url;

		// Set authorization flags
		if ($this->auth) {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($ch, CURLOPT_USERPWD, $this->auth);
		}

		$requestHeaders = array();

		// Add custom headers
		if (!empty($this->requestMessage->headers)) {
			foreach ($this->requestMessage->headers as $field => $value) {
				$requestHeaders[] = $field.': '.$value;
			}
		}

		// Add cookie header
		if (!empty($this->requestMessage->cookies)) {
			$cookieList = '';
			foreach ($this->requestMessage->getCookie() as $cookieName=>$cookieValue) {
				$cookieList .= ($cookieList ? '; ' : '').$cookieName.'='.$cookieValue;
			}
			$requestHeaders[] = 'Cookie: '.$cookieList;
		}

		if (!empty($requestHeaders)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		}

		if ($this->requestEncoding) {
			curl_setopt($ch, CURLOPT_ENCODING, $this->requestEncoding);
		}

		return $ch;
	}

	/**
	 * Execute curl query and return response body.
	 *
	 * @param string  $ch                Curl instance to execute.
	 * @param integer $acceptContentType Expected response content type.
	 * @param integer $returnFormat      Type of data to be returned.
	 *
	 * @return mixed
	 */
	protected function curlExec($ch, $acceptContentType, $returnFormat)
	{
		if ($this->logger) {
			// Log request information
			$this->logger->setRequestInfo($this->lastUrl,
					$this->requestGetParams,
					$this->requestPostParams,
					$this->requestMessage->rawBody);
		}

		if (defined('YII_DEBUG') && constant('YII_DEBUG')) {
			// Start Yii Profiling
			$yiiProfilingToken = get_class($this).'::'.$this->lastVerb.'('.$this->lastUrl.')';
			$yiiProfilingCategory = 'bogo-yii.curl-request';
			Yii::beginProfile($yiiProfilingToken, $yiiProfilingCategory);

			// Start cURL debugging
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			$curlVerboseFile = fopen('php://temp', 'rw+');
			curl_setopt($ch, CURLOPT_STDERR, $curlVerboseFile);
		} else {
			$yiiProfilingToken = '';
		}

		// Initialize response body
		$this->responseMessage->reset();

		// Execute the HTTP request
		$curlReturnValue = curl_exec($ch);

		if ($yiiProfilingToken) {
			// End profiling
			Yii::endProfile($yiiProfilingToken, $yiiProfilingCategory);

			// Retrieve ferbose info
			rewind($curlVerboseFile);
			$this->curlVerboseInfo = stream_get_contents($curlVerboseFile);
			fclose($curlVerboseFile);
		} else {
			$this->curlVerboseInfo = null;
		}

		// Network error
		if ($this->responseMessage->rawBody === null) {
			// WRITEFUNCTION did not return any content. Probably an error
			if ($curlReturnValue === false) {
				$error = curl_error($ch);

//			if ($this->_log) {
				// Log curl error
//				$this->_log->setCurlError(curl_errno($ch), $error);
//			}

				curl_close($ch);
				throw new Exception("CURL Error: ".htmlspecialchars($error));
			}
		} else {
			// WRITEFUNCTION returned content. So far so good.
//			if ($curlReturnValue === false) {
				// Returned data is false. Probably some ill-formed server response, i.e.
				// Content-length missing or bad "Transfer-encoding: chunked" response.
//			}
		}

		// Collect response information and close
		$this->responseDetails = curl_getinfo($ch);

		// Done with this descriptor
		curl_close($ch);

		if ($this->logger) {
			// Log successful response information
			$this->logger->setResponseInfo($this->responseDetails['http_code'],
					$this->responseMessage->rawBody, $this->responseMessage->headers);
		}

		// Network operation succeeded but way may have an HTTP error
		if ($this->responseDetails['http_code'] >= 400) {
			throw new Exception($this->responseMessage->rawBody, $this->responseDetails['http_code']);
		}

		// Return content
		switch ($acceptContentType) {

			// JSON
			case self::CONTENT_JSON:
				if (empty($this->responseMessage->rawBody)) {
					throw new Exception('CURL returned empty body');
				}

				switch ($returnFormat) {
					case self::RETURN_OBJECT:
						$this->responseMessage->rawBody = json_decode($this->responseMessage->rawBody, false);
						break;

					//				case self::RETURN_ASSOC:
					default:
						$this->responseMessage->rawBody = json_decode($this->responseMessage->rawBody, true);
						break;
				}

				if (is_null($this->responseMessage->rawBody)) {
					// When expecting JSON, empty responses are probably invalid
					if ($this->logger) {
						$error = 'Response body does not have proper JSON format';

						$jsonErrorCode = json_last_error();

						switch ($jsonErrorCode) {
							case JSON_ERROR_NONE:
								$jsonErrorMessage = false;
							case JSON_ERROR_DEPTH:
								$jsonErrorMessage =  'Maximum stack depth exceeded';
							case JSON_ERROR_STATE_MISMATCH:
								$jsonErrorMessage =  'Invalid or malformed JSON';
							case JSON_ERROR_CTRL_CHAR:
								$jsonErrorMessage =  'Control character error, possibly incorrectly encoded';
							case JSON_ERROR_SYNTAX:
								$jsonErrorMessage =  'Syntax error';
							case JSON_ERROR_UTF8:
								$jsonErrorMessage =  'Malformed UTF-8 characters, possibly incorrectly encoded';
							default:
								$jsonErrorMessage =  'Unsupported error code '.$jsonErrorCode;
						}


						if ($jsonErrorMessage) {
							$error .= ' ('.$jsonErrorMessage.')';
						}

						$this->logger->onError(CBCurlLogger::ERROR_MALFORMED_RESPONSE, $error);
					}
				}
				break;

			// XML
			case self::CONTENT_XML:
				if (empty($this->responseMessage->rawBody)) {
					throw new Exception('CURL returned empty body');
				}
				$this->responseMessage->rawBody = simplexml_load_string($this->responseMessage->rawBody);
				break;

			// Default (RAW)
			default:
		}

		// Stop using this _log (so subsequent calls are forced to use a new one)
//		$this->logger = null;

		return $this->responseMessage->rawBody;
	}

	/**
	 * Return last url called.
	 *
	 * @return string
	 */
	public function getLastUrl()
	{
		return $this->lastUrl;
	}

	/**
	 * Start logging this rest call.
	 *
	 * @param array $options Logger component options.
	 *
	 * @return CBCurlLogger
	 */
	public function createLogger(array $options = array())
	{
		if (isset($options['class'])) {
			$loggerClassName =  $options['class'];
			unset($options['class']);
		} else {
			$loggerClassName = 'CBCurlLogger';
		}

		// Instantiate
		$this->logger = new $loggerClassName();

		// Initialize
		foreach ($options as $optionName=>$optionValue) {
			$this->logger->$optionName = $optionValue;
		}

		return $this->logger;
	}

	/**
	 * Return currently active logger (if any).
	 *
	 * @return CBCurlLogger
	 */
	public function getLogger()
	{
		return $this->logger;
	}
}
