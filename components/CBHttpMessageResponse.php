<?php
/**
 * Response HTTP Message.
 *
 * @since 1.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBHttpMessageResponse extends CBHttpMessage
{
	/**
	 * HTTP Response Code.
	 *
	 * @var string
	 */
	public $code;

	/**
	 * Reset all internal variables.
	 *
	 * @return CBHttpMessageResponse
	 */
	public function reset()
	{
		parent::reset();

		$this->code = null;

		return $this;
	}
}