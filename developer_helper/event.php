<?php

class Event extends Base
{
	public $sender;
	public $params;
	
	public function __construct($sender=NULL, $params=NULL)
	{
		$this->sender = $sender;
		$this->params = $params;
	}
	
}