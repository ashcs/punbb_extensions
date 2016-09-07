<?php











namespace Composer\Installer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\PolicyInterface;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\Repository\CompositeRepository;






class PackageEvent extends InstallerEvent
{



private $operation;















public function __construct($eventName, Composer $composer, IOInterface $io, $devMode, PolicyInterface $policy, Pool $pool, CompositeRepository $installedRepo, Request $request, array $operations, OperationInterface $operation)
{
parent::__construct($eventName, $composer, $io, $devMode, $policy, $pool, $installedRepo, $request, $operations);

$this->operation = $operation;
}






public function getOperation()
{
return $this->operation;
}
}
