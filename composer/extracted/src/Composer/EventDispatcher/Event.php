<?php











namespace Composer\EventDispatcher;






class Event
{



protected $name;




protected $args;




protected $flags;




private $propagationStopped = false;








public function __construct($name, array $args = array(), array $flags = array())
{
$this->name = $name;
$this->args = $args;
$this->flags = $flags;
}






public function getName()
{
return $this->name;
}






public function getArguments()
{
return $this->args;
}






public function getFlags()
{
return $this->flags;
}






public function isPropagationStopped()
{
return $this->propagationStopped;
}




public function stopPropagation()
{
$this->propagationStopped = true;
}
}
