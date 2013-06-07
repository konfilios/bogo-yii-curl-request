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
	private $rawBody = null;

	/**
	 * Headers as key-value pairs.
	 *
	 * @var string[]
	 */
	private $headers = array();

	/**
	 * Cookies.
	 *
	 * @var array[]
	 */
	private $cookies = array();

	/**
	 * Batch assignment of headers.
	 *
	 * @param array $fields
	 * @return CBHttpMessage
	 */
	public function setHeaders(array $fields)
	{
		foreach ($fields as $field=>$value) {
			$this->setHeader($field, $value);
		}

		return $this;
	}

	/**
	 * Set a header.
	 *
	 * If value is null, then the header is removed (if it's there).
	 *
	 * @param string $field Name of request header.
	 * @param string $value Value of request header.
	 * @return CBHttpMessage
	 */
	public function setHeader($field, $value = null)
	{
		if ($value === null) {
			if (isset($this->headers[$field])) {
				unset($this->headers[$field]);
			}
		} else {
			$this->headers[$field] = $value;
		}

		return $this;
	}

	/**
	 * Get a specific header or all if no $field is given.
	 *
	 * @param string $field
	 * @return mixed
	 */
	public function getHeader($field = null)
	{
		if ($field === null) {
			return $this->headers;
		} else {
			$field = strtolower($field);
			return isset($this->headers[$field]) ? $this->headers[$field] : null;
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
	 * Append a string to the raw body.
	 *
	 * @param string $rawBodyString
	 * @return CBHttpMessage
	 */
	public function appendRawBodyString($rawBodyString)
	{
		if ($this->rawBody === null) {
			$this->rawBody = $rawBodyString;
		} else {
			$this->rawBody .= $rawBodyString;
		}

		return $this;
	}

	/**
	 * True if there's non-empty raw body.
	 *
	 * @return boolean
	 */
	public function hasRawBody()
	{
		return ($this->rawBody !== null);
	}

	/**
	 * Get raw body.
	 *
	 * @return string
	 */
	public function getRawBody()
	{
		return $this->rawBody;
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