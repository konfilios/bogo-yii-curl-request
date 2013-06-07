<?php
/**
 * Request HTTP Message.
 *
 * @since 2.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBHttpMessageRequest extends CBHttpMessage
{
	/**
	 * HTTP Request Verb/Method.
	 *
	 * Values are GET, POST, PUT, DELETE, etc.
	 *
	 * @var string
	 */
	private $httpVerb;

	/**
	 * HTTP Request URI.
	 *
	 * @var type
	 */
	private $uri;

	/**
	 * Attached files.
	 *
	 * @var string[]
	 */
	private $files;

	/**
	 * HTTP GET parameters.
	 *
	 * @var string[]
	 */
	private $getParams;

	/**
	 * HTTP POST parameters.
	 *
	 * @var string[]
	 */
	private $postParams;

	/**
	 * User-defined fields.
	 *
	 * @var array
	 */
	private $userFields = array();

	/**
	 * HTTP Request Verb/Method.
	 *
	 * @return string
	 */
	public function getHttpVerb()
	{
		return $this->httpVerb;
	}

	/**
	 * HTTP Request Verb/Method.
	 *
	 * @param type $httpVerb
	 * @return CBHttpMessageRequest
	 */
	public function setHttpVerb($httpVerb)
	{
		$this->httpVerb = $httpVerb;

		return $this;
	}

	/**
	 * HTTP Request URI.
	 *
	 * @return string
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * HTTP Request URI.
	 *
	 * @param string $uri
	 * @return CBHttpMessageRequest
	 */
	public function setUri(type $uri)
	{
		$this->uri = $uri;

		return $this;
	}

	/**
	 * Batch assignment of user fields.
	 *
	 * @param array $fields
	 * @return CBHttpMessageRequest
	 */
	public function setUserFields(array $fields)
	{
		foreach ($fields as $field=>$value) {
			$this->setUserField($field, $value);
		}

		return $this;
	}

	/**
	 * Set a user field.
	 *
	 * If value is null, then the user field is removed (if it's there).
	 *
	 * @param string $field Name of request header.
	 * @param string $value Value of request header.
	 * @return CBHttpMessageRequest
	 */
	public function setUserField($field, $value = null)
	{
		if ($value === null) {
			if (isset($this->userFields[$field])) {
				unset($this->userFields[$field]);
			}
		} else {
			$this->userFields[$field] = $value;
		}

		return $this;
	}

	/**
	 * Retrieve a specific USER field or all.
	 *
	 * @param string $field Parameter name.
	 * @return mixed
	 */
	public function getUserField($field = null)
	{
		return ($field === null) ? $this->userFields : (isset($this->userFields[$field]) ? $this->userFields[$field] : null);
	}

	/**
	 * Batch assignment of files.
	 *
	 * @param array $fields
	 * @return CBHttpMessageRequest
	 */
	public function setFiles(array $fields)
	{
		foreach ($fields as $field=>$value) {
			$this->setFile($field, $value);
		}

		return $this;
	}

	/**
	 * Queue $filename for uploading as $field.
	 *
	 * @param string $field Name of file in the request.
	 * @param string $value Filename to upload or null to unset.
	 * @return CBHttpMessageRequest
	 */
	public function setFile($field, $value = null)
	{
		if (is_null($value)) {
			// Unsetting
			if (isset($this->files[$field])) {
				unset($this->files[$field]);
			}
		} else {
			// Setting new value
			$this->files[$field] = $value;
		}

		return $this;
	}

	/**
	 * Retrieve a specific FILE field or all.
	 *
	 * @param string $field Parameter name.
	 * @return mixed
	 */
	public function getFile($field = null)
	{
		return ($field === null) ? $this->files : (isset($this->files[$field]) ? $this->files[$field] : null);
	}

	/**
	 * Batch assignment of GET parameters.
	 *
	 * @param array $fields
	 * @return CBHttpMessageRequest
	 */
	public function setGetParams(array $fields)
	{
		foreach ($fields as $field=>$value) {
			$this->setGetParam($field, $value);
		}

		return $this;
	}

	/**
	 * Set value for a GET field.
	 *
	 * @param string $field Parameter name.
	 * @param string $value New value or null to unset.
	 * @return CBHttpMessageRequest
	 */
	public function setGetParam($field, $value = null)
	{
		if (is_null($value)) {
			// Unsetting
			if (isset($this->getParams[$field])) {
				unset($this->getParams[$field]);
			}
		} else {
			// Setting new value
			$this->getParams[$field] = $value;
		}

		return $this;
	}

	/**
	 * Retrieve a specific GET parameter or all.
	 *
	 * @param string $field Parameter name.
	 * @return mixed
	 */
	public function getGetParam($field = null)
	{
		return ($field === null) ? $this->getParams : (isset($this->getParams[$field]) ? $this->getParams[$field] : null);
	}

	/**
	 * Batch assignment of POST parameters.
	 *
	 * @param array $fields
	 * @return CBHttpMessageRequest
	 */
	public function setPostParams(array $fields)
	{
		foreach ($fields as $field=>$value) {
			$this->setPostParam($field, $value);
		}

		return $this;
	}

	/**
	 * Set value for a POST field.
	 *
	 * @param string $field Parameter name.
	 * @param string $value New value or null to unset.
	 * @return CBHttpMessageRequest
	 */
	public function setPostParam($field, $value = null)
	{
		if (is_null($value)) {
			// Unsetting
			if (isset($this->postParams[$field])) {
				unset($this->postParams[$field]);
			}
		} else {
			$this->postParams[$field] = $value;
		}

		return $this;
	}

	/**
	 * Retrieve a specific POST parameter or all.
	 *
	 * @param string $field Parameter name.
	 * @return mixed
	 */
	public function getPostParam($field = null)
	{
		return ($field === null) ? $this->postParams : (isset($this->postParams[$field]) ? $this->postParams[$field] : null);
	}

	/**
	 * Wrap into a call.
	 *
	 * @return CBHttpCall
	 */
	public function createCall($callClass = 'CBHttpCallCurl')
	{
		return new $callClass($this);
	}

	/**
	 * Create a new HTTP Request message.
	 *
	 * @param string $httpVerb
	 * @param string $uri
	 * @return CBHttpMessageRequest
	 */
	static public function create($httpVerb = null, $uri = null)
	{
		$message = new CBHttpMessageRequest();

		$message->httpVerb = $httpVerb;
		$message->uri = $uri;

		return $message;
	}
}