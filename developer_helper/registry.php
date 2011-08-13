<?php

class Registry extends Base
{
	private static $instance;
	
	private function __construct()
	{
		parent::__construct();
	}

	static function instance()
	{
		if (!(isset(self::$instance)))
		{
			$instance = new self;
		}
		return self::$instance;
	}
	
	static function set($key, $value)
	{
		self::$instance->$key = $value;
	}
	
	static function get($key)
	{
		return self::$instance->$key;
	}
}