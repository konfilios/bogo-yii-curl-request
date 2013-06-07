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
	public $httpVerb;

	/**
	 * HTTP Request URI.
	 *
	 * @var type
	 */
	public $uri;

	/**
	 * Attached files.
	 *
	 * @var string[]
	 */
	public $files;

	/**
	 * HTTP GET parameters.
	 *
	 * @var string[]
	 */
	public $getParams;

	/**
	 * HTTP POST parameters.
	 *
	 * @var string[]
	 */
	public $postParams;

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

	/**
	 * Queue $filename for uploading as $field.
	 *
	 * @param string $field    Name of file in the request.
	 * @param string $filename Filename to upload or null to unset.
	 *
	 * @return CBHttpMessageRequest
	 */
	public function setFile($field, $filename = null)
	{
		$this->files[$field] = "@".realpath($filename);

		return $this;
	}

	/**
	 * Set value for a GET field.
	 *
	 * @param string $field Parameter name.
	 * @param string $value New value or null to unset.
	 *
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
			$this->getParams[$field] = (is_array($value) || is_object($value)) ?
					json_encode($value) : $value;
		}

		return $this;
	}

	/**
	 * Retrieve a request get parameter (if already set).
	 *
	 * @param string $field Parameter name.
	 * @return mixed Value of get parameter previously set using setGetParam() or null
	 */
	public function getGetParam($field)
	{
		return isset($this->getParams[$field]) ? $this->getParams[$field] : null;
	}

	/**
	 * Set value for a POST field.
	 *
	 * @param string $field Parameter name.
	 * @param string $value New value or null to unset.
	 *
	 * @return CBHttpMessageRequest
	 */
	public function setPostParam($field, $value = null)
	{
		if (is_null($value)) {
			// Unsetting
			if (isset($this->requestPostParams[$field])) {
				unset($this->requestPostParams[$field]);
			}
		} else {
			$this->requestPostParams[$field] = (is_array($value) || is_object($value)) ?
					json_encode($value) : $value;
		}

		return $this;
	}
}