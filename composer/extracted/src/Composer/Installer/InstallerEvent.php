<?php











namespace Composer\Installer;

use Composer\Composer;
use Composer\DependencyResolver\PolicyInterface;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\EventDispatcher\Event;
use Composer\IO\IOInterface;
use Composer\Repository\CompositeRepository;






class InstallerEvent extends Event
{



private $composer;




private $io;




private $devMode;




private $policy;




private $pool;




private $installedRepo;




private $request;




private $operations;














public function __construct($eventName, Composer $composer, IOInterface $io, $devMode, PolicyInterface $policy, Pool $pool, CompositeRepository $installedRepo, Request $request, array $operations = array())
{
parent::__construct($eventName);

$this->composer = $composer;
$this->io = $io;
$this->devMode = $devMode;
$this->policy = $policy;
$this->pool = $pool;
$this->installedRepo = $installedRepo;
$this->request = $request;
$this->operations = $operations;
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




public function getPolicy()
{
return $this->policy;
}




public function getPool()
{
return $this->pool;
}




public function getInstalledRepo()
{
return $this->installedRepo;
}




public function getRequest()
{
return $this->request;
}




public function getOperations()
{
return $this->operations;
}
}
