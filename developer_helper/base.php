<?php
abstract class Base
{
	protected $properties = array();

	function __set($key, $value)
	{
		$this->properties[$key] = $value;
	}
	
	function __get($key)
	{
		if (isset($this->properties[$key]))
		{
			return $this->properties[$key];
		}
		
		return NULL;
	}
	
	function __isset($key)
	{
		if (isset($this->properties[$key]))
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	function __unset($key)
	{
		if (isset($this->properties[$key]))
		{
			unset($this->properties[$key]);
		}
	}
}