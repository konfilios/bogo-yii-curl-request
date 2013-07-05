<?php
/**
 * CBHttpCall event.
 *
 * @since 2.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBHttpCallEvent extends CEvent
{
	/**
	 * Call this event is about.
	 *
	 * @var CBHttpCall
	 */
	public $call;

	public function __construct($sender = null, $params = null, $call = null)
	{
		parent::__construct($sender, $params);
		$this->call = $call;
	}
}