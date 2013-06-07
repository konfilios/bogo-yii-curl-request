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
		return json_decode($this->rawBody, $returnAssoc);
	}

	/**
	 * XML representation of body.
	 *
	 * @return SimpleXMLElement
	 */
	public function getBodyAsXml()
	{
		return simplexml_load_string($this->rawBody);
	}
}