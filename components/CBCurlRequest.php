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
	 * Set outgoing http data encoding
	 *
	 * @param string $encoding
	 * @return CBCurlRequest
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
	 * @return CBCurlRequest
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
	 * @return CBCurlRequest
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
		$this->requestMessage = new CBHttpMessageRequest();
		$this->responseMessage = new CBHttpMessage();
	}

	/**
	 * Reset all request and response data.
	 *
	 * @return CBCurlRequest
	 */
	public function reset()
	{
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
	 * @return CBCurlRequest
	 */
	public function setRequestCookie($cookieName, $cookieValue)
	{
		$this->requestMessage->setCookie($cookieName, $cookieValue);

		return $this;
	}

	/**
	 * Get a specific request cookie value or all (if no $cookieName is given).
	 *
	 * @param string $cookieName Cookie name.
	 * @return mixed
	 */
	public function getRequestCookie($cookieName = '')
	{
		return $this->requestMessage->getCookie($cookieName);
	}

	/**
	 * Add a request header.
	 *
	 * @param string $field Name of request header.
	 * @param string $value Value of request header.
	 *
	 * @return CBCurlRequest
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
	 * @return CBCurlRequest
	 */
	public function setRequestFile($field, $filename = null)
	{
		$this->requestMessage->setFile($field, $filename);

		return $this;
	}

	/**
	 * Set value for a POST field.
	 *
	 * @param string $field Parameter name.
	 * @param string $value New value or null to unset.
	 *
	 * @return CBCurlRequest
	 */
	public function setRequestPostParam($field, $value = null)
	{
		$this->requestMessage->setPostParam($field, $value);

		return $this;
	}

	/**
	 * Set value for a GET field.
	 *
	 * @param string $field Parameter name.
	 * @param string $value New value or null to unset.
	 * @return CBCurlRequest
	 */
	public function setRequestGetParam($field, $value = null)
	{
		$this->requestMessage->setGetParam($field, $value);

		return $this;
	}

	/**
	 * Retrieve a request get parameter (if already set).
	 *
	 * @param string $field Parameter name.
	 * @return mixed Value of get parameter previously set using setGetParam() or null
	 */
	public function getRequestGetParam($field)
	{
		return $this->requestMessage->getGetParam($field);
	}

	/**
	 * Set JSON body. If $data is in string format, it's left as is. Otherwise it's json encoded.
	 *
	 * @param mixed $data Json data.
	 *
	 * @return CBCurlRequest
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
	 * @return CBCurlRequest
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
			if (empty($this->requestMessage->files)) {
				// Content-type: application/x-www-form-urlencoded
				return http_build_query($this->requestMessage->postParams, '', '&');
			} else {
				// Content-type: multipart/form-data
				return $this->requestMessage->postParams + $this->requestMessage->files;
			}
		}
	}

	/**
	 * Issue a POST request.
	 *
	 * @param string  $url               Target url.
	 * @param integer $acceptContentType Expected response content type.
	 * @param integer $returnFormat      Type of data to be returned.
	 * @return mixed
	 */
	public function post($url, $acceptContentType = 0, $returnFormat = 0)
	{
		$this->requestMessage->verb = 'POST';
		$this->requestMessage->uri = $url;

		$this->curlExec();

		return $this->getProcessedResponseBody($acceptContentType, $returnFormat);
	}

	/**
	 * Issue a GET request.
	 *
	 * @param string  $url               Target url.
	 * @param integer $acceptContentType Expected response content type.
	 * @param integer $returnFormat      Type of data to be returned.
	 * @return mixed
	 */
	public function get($url, $acceptContentType = 0, $returnFormat = 0)
	{
		$this->requestMessage->verb = 'GET';
		$this->requestMessage->uri = $url;

		$this->curlExec();

		return $this->getProcessedResponseBody($acceptContentType, $returnFormat);
	}

	/**
	 * Issue a DELETE request.
	 *
	 * @param string  $url               Target url.
	 * @param integer $acceptContentType Expected response content type.
	 * @param integer $returnFormat      Type of data to be returned.
	 * @return mixed
	 */
	public function delete($url, $acceptContentType = 0, $returnFormat = 0)
	{
		$this->requestMessage->verb = 'DELETE';
		$this->requestMessage->uri = $url;

		$this->curlExec();

		return $this->getProcessedResponseBody($acceptContentType, $returnFormat);
	}

	/**
	 * Issue a PUT request.
	 *
	 * @param string  $url               Target url.
	 * @param integer $acceptContentType Expected response content type.
	 * @param integer $returnFormat      Type of data to be returned.
	 * @return mixed
	 */
	public function put($url, $acceptContentType = 0, $returnFormat = 0)
	{
		$this->requestMessage->verb = 'PUT';
		$this->requestMessage->uri = $url;

		$this->curlExec();

		return $this->getProcessedResponseBody($acceptContentType, $returnFormat);
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

					if ($cookieName === null) {
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
	 * Return response body.
	 *
	 * @param integer $acceptContentType Expected response content type.
	 * @param integer $returnFormat Type of data to be returned.
	 * @return mixed
	 * @throws Exception
	 */
	private function getProcessedResponseBody($acceptContentType, $returnFormat)
	{
		// Return content
		switch ($acceptContentType) {

			// JSON
			case self::CONTENT_JSON:
				if (empty($this->responseMessage->rawBody)) {
					throw new Exception('CURL returned empty body');
				}
				return $this->responseMessage->getBodyAsJson($returnFormat != self::RETURN_OBJECT);

			// XML
			case self::CONTENT_XML:
				if (empty($this->responseMessage->rawBody)) {
					throw new Exception('CURL returned empty body');
				}
				return $this->responseMessage->getBodyAsXml();

			// Default (RAW)
			default:
				return $this->responseMessage->rawBody;
		}
	}

	/**
	 * Execute a curl request based on current request message.
	 */
	protected function curlExec()
	{
		$ch = curl_init();

		//
		// Prepare final url
		//
		$url = $this->requestMessage->uri;

		if (!empty($this->requestMessage->getParams)) {
			$url .= '?'.http_build_query($this->requestMessage->getParams, '', '&');
		}

		//
		// Set basic options
		//
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'parseResponseHeader'));
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this, 'parseResponseBody'));

		// Set authorization flags
		if ($this->auth) {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($ch, CURLOPT_USERPWD, $this->auth);
		}

		//
		// Build list of headers
		//
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

		//
		// Verb-specific handler
		//
		switch (strtolower($this->requestMessage->verb)) {
		case 'post':
			// It's a post request
			curl_setopt($ch, CURLOPT_POST, 1);

			// Build and set body
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->compileRequestBody());
			break;

		case 'delete':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			break;

		case 'put':
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
			break;

		default:
		}

		if ($this->logger) {
			// Log request information
			$this->logger->setRequestInfo($this->requestMessage);
		}

		if (defined('YII_DEBUG') && constant('YII_DEBUG')) {
			// Start Yii Profiling
			$yiiProfilingToken = get_class($this).'::'.$this->requestMessage->verb.'('.$this->requestMessage->uri.')';
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

		//
		// Execute the HTTP request
		//
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

				if ($this->logger) {
					// Log curl error
					$this->logger->setCurlError(curl_errno($ch), $error);
				}

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

		$this->responseMessage->code = $this->responseDetails['http_code'];

		if ($this->logger) {
			// Log successful response information
			$this->logger->setResponseInfo($this->responseMessage);
		}

		// Network operation succeeded but way may have an HTTP error
		if ($this->responseMessage->code >= 400) {
			throw new Exception($this->responseMessage->rawBody ?: 'Server error '.$this->responseMessage->code, $this->responseMessage->code);
		}
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

	/**
	 * Executes request message and returns response message.
	 *
	 * @param CBHttpMessageRequest $requestMessage
	 * @return CBHttpMessageResponse
	 */
	public function exec(CBHttpMessageRequest $requestMessage)
	{
		$this->requestMessage = $requestMessage;

		$this->curlExec();

		return $this->responseMessage;
	}
}
