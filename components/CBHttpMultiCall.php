<?php
/**
 * Multi-call execution.
 *
 * @since 2.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
abstract class CBHttpMultiCall
{
	/**
	 * List of calls to be executed.
	 *
	 * @var CBHttpCall[]
	 */
	private $calls = array();

	/**
	 * Error code.
	 *
	 * @var integer
	 */
	protected $errorCode = 0;

	/**
	 * Error code.
	 *
	 * @return integer
	 */
	public function getErrorCode()
	{
		return $this->errorCode;
	}

	/**
	 * Set a call with a given key.
	 *
	 * @param string $key
	 * @param CBHttpCall $call
	 */
	protected function setCall($key, CBHttpCall $call = null)
	{
		if ($call === null) {
			if (isset($this->calls[$key])) {
				unset($this->calls[$key]);
			}
		} else {
			$this->calls[$key] = $call;
		}
	}

	/**
	 * List of calls to be executed.
	 *
	 * @return CBHttpCall[]
	 */
	public function getCalls()
	{
		return $this->calls;
	}

	/**
	 * Execute all calls.
	 *
	 * @return CBHttpMultiCall
	 */
	abstract public function exec();
}