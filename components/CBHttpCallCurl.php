<?php
/**
 * Curl HTTP call.
 *
 * @since 2.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBHttpCallCurl extends CBHttpCall
{
	/**
	 * Verbose CURL info about the request.
	 *
	 * @var string
	 */
	private $curlVerboseInfo = null;

	/**
	 * File receiving verbose curl info.
	 *
	 * @var file
	 */
	private $curlVerboseFile = null;

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
	 * Compile array of request headers.
	 *
	 * @return string[]
	 */
	protected function compileRequestHeaderArray()
	{
		$requestHeaders = array();

		// Add custom headers
		foreach ($this->requestMessage->headers as $field => $value) {
			$requestHeaders[] = $field.': '.$value;
		}

		// Add cookie header
		if (!empty($this->requestMessage->cookies)) {
			$cookieList = '';
			foreach ($this->requestMessage->getCookie() as $cookieName=>$cookieValue) {
				$cookieList .= ($cookieList ? '; ' : '').$cookieName.'='.$cookieValue;
			}
			$requestHeaders[] = 'Cookie: '.$cookieList;
		}

		return $requestHeaders;
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
		$this->responseMessage->parseHeaderLine($headerLine);

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
	 * Initialize a cURL session.
	 *
	 * This method is publicly exposed in order to be used by curl multi-calls.
	 *
	 * @return resource
	 */
	public function curlInit()
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
//		if ($this->auth) {
//			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
//			curl_setopt($ch, CURLOPT_USERPWD, $this->auth);
//		}

		//
		// Build list of headers
		//
		$requestHeaders = $this->compileRequestHeaderArray();

		if (!empty($requestHeaders)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		}

		//
		// Verb-specific handler
		//
		switch (strtolower($this->requestMessage->httpVerb)) {
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

		if ($this->getInDebugMode()) {
			// Start cURL debugging
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			$this->curlVerboseFile = fopen('php://temp', 'rw+');
			curl_setopt($ch, CURLOPT_STDERR, $this->curlVerboseFile);
		}

		// Consider this as the beggining of the call
		$this->setState(CBHttpCallCurl::STATE_RUNNING);

		return $ch;
	}

	/**
	 * Close a cURL session.
	 *
	 * This method is publicly exposed in order to be used by curl multi-calls.
	 *
	 * @param resource $ch
	 */
	public function curlClose($ch)
	{
		// Consider this the end of the call
		$this->setState(CBHttpCallCurl::STATE_COMPLETED);

		if ($this->getInDebugMode()) {
			// Retrieve ferbose info
			rewind($this->curlVerboseFile);
			$this->curlVerboseInfo = stream_get_contents($this->curlVerboseFile);
			fclose($this->curlVerboseFile);
		}

		$this->errorMessage = curl_error($ch);
		$this->errorCode = curl_errno($ch);

		curl_close($ch);
	}


	/**
	 * Executes request message and returns response message.
	 *
	 * @return CBHttpMessageResponse
	 */
	public function exec()
	{
		// Initialize curl
		$ch = $this->curlInit();

		// Execute the HTTP request
		$curlReturnValue = curl_exec($ch);

		// Everything cool
		$this->curlClose($ch);

		// Network error
		if ($this->responseMessage->rawBody === null) {
			// WRITEFUNCTION did not return any content. Probably an error
			if ($curlReturnValue === false) {
				throw new CBHttpCallException($this->errorMessage, $this->errorCode);
			}
		} else {
			// WRITEFUNCTION returned content. So far so good.
//			if ($curlReturnValue === false) {
				// Returned data is false. Probably some ill-formed server response, i.e.
				// Content-length missing or bad "Transfer-encoding: chunked" response.
//			}
		}

		return $this->responseMessage;
	}

	/**
	 * Verbose cURL info.
	 *
	 * @return string
	 */
	public function getDebugInfo()
	{
		return $this->curlVerboseInfo;
	}
}
