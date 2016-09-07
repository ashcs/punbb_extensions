<?php











namespace Composer\Script;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\Event as BaseEvent;







class Event extends BaseEvent
{



private $composer;




private $io;




private $devMode;











public function __construct($name, Composer $composer, IOInterface $io, $devMode = false, array $args = array(), array $flags = array())
{
parent::__construct($name, $args, $flags);
$this->composer = $composer;
$this->io = $io;
$this->devMode = $devMode;
}






public function getComposer()
{
return $this->composer;
}






public function getIO()
{
return $this->io;
}






public function isDevMode()
{
return $this->devMode;
}
}
