<?php
abstract class Base implements SplSubject
{
	private	$storage;
	private $state;
	protected $properties = array();

		
	function __construct()
	{
		$this->storage = new SplObjectStorage;
	}
	
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
	
	function attach(SplObserver $observer)
	{
		$this->storage->attach($observer);
	}
	
	function detach(SplObserver $observer)
	{
		$this->storage->detach($observer);
	}
	
	function notify()
	{
		foreach ($this->storage as $cur_observer)
		{
			$cur_observer->update($this);
		}
	}
	
}