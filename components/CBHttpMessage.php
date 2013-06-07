<?php
/**
 * Base HTTP Message.
 *
 * @since 2.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBHttpMessage
{
	/**
	 * Body (payload).
	 *
	 * @var string
	 */
	public $rawBody = null;

	/**
	 * Headers as key-value pairs.
	 *
	 * @var string[]
	 */
	public $headers = array();

	/**
	 * Cookies.
	 *
	 * @var array[]
	 */
	public $cookies = array();

	/**
	 * User-defined fields.
	 *
	 * @var array
	 */
	public $userFields = array();

	/**
	 * Set raw body.
	 *
	 * @param string $rawBody
	 * @return CBHttpMessage
	 */
	public function setRawBody($rawBody)
	{
		$this->rawBody = $rawBody;

		return $this;
	}

	/**
	 * Add a header.
	 *
	 * @param string $field Name of request header.
	 * @param string $value Value of request header.
	 * @return CBHttpMessage
	 */
	public function setHeader($field, $value = null)
	{
		$this->headers[$field] = $value;

		return $this;
	}

	/**
	 * Get a specific response header or all (if no $field is given).
	 *
	 * @param string $headerName Header name.
	 *
	 * @return mixed
	 */
	public function getHeader($headerName = '')
	{
		if (empty($headerName)) {
			return $this->headers;
		} else {
			$headerName = strtolower($headerName);
			return isset($this->headers[$headerName]) ? $this->headers[$headerName] : null;
		}
	}

	/**
	 * Get a specific cookie attributes or all (if no $cookieName is given).
	 *
	 * @param string $cookieName Cookie name.
	 *
	 * @return array
	 */
	public function getCookieAttributes($cookieName = '')
	{
		if (empty($cookieName)) {
			return $this->cookies;
		} else {
			return isset($this->cookies[$cookieName]) ? $this->cookies[$cookieName] : array();
		}
	}

	/**
	 * Get a specific cookie value or all (if no $cookieName is given).
	 *
	 * @param string $cookieName Cookie name.
	 *
	 * @return mixed
	 */
	public function getCookie($cookieName = '')
	{
		if (empty($cookieName)) {
			$cookieValues = array();
			foreach ($this->cookies as $cookieName=>$cookieAttributes) {
				$cookieValues[$cookieName] = $cookieAttributes['value'];
			}
			return $cookieValues;
		} else {
			return isset($this->cookies[$cookieName]['value']) ? $this->cookies[$cookieName]['value'] : null;
		}
	}

	/**
	 * Set a cookie with its name and attributes,
	 *
	 * @param string $cookieName
	 * @param string $cookieValue
	 * @param array $cookieAttributes
	 * @return CBHttpMessage
	 */
	public function setCookie($cookieName, $cookieValue, $cookieAttributes = array())
	{
		$cookieAttributes['value'] = $cookieValue;

		$this->cookies[$cookieName] = $cookieAttributes;

		return $this;
	}

	/**
	 * JSON decode body.
	 *
	 * @return mixed
	 */
	public function getBodyAsJson($returnAssoc = true)
	{
		$json = json_decode($this->responseMessage->rawBody, $returnAssoc);

		if (!is_null($json)) {
			return $json;
		}

		// When expecting JSON, empty responses are probably invalid
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

		throw new Exception($error, $jsonErrorCode);
	}

	/**
	 * XML representation of body.
	 *
	 * @return SimpleXMLElement
	 */
	public function getBodyAsXml()
	{
		return simplexml_load_string($this->responseMessage->rawBody);
	}
}