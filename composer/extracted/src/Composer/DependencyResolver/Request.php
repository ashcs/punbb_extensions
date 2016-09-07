<?php











namespace Composer\DependencyResolver;

use Composer\Semver\Constraint\ConstraintInterface;




class Request
{
protected $jobs;

public function __construct()
{
$this->jobs = array();
}

public function install($packageName, ConstraintInterface $constraint = null)
{
$this->addJob($packageName, 'install', $constraint);
}

public function update($packageName, ConstraintInterface $constraint = null)
{
$this->addJob($packageName, 'update', $constraint);
}

public function remove($packageName, ConstraintInterface $constraint = null)
{
$this->addJob($packageName, 'remove', $constraint);
}






public function fix($packageName, ConstraintInterface $constraint = null)
{
$this->addJob($packageName, 'install', $constraint, true);
}

protected function addJob($packageName, $cmd, ConstraintInterface $constraint = null, $fixed = false)
{
$packageName = strtolower($packageName);

$this->jobs[] = array(
'cmd' => $cmd,
'packageName' => $packageName,
'constraint' => $constraint,
'fixed' => $fixed,
);
}

public function updateAll()
{
$this->jobs[] = array('cmd' => 'update-all');
}

public function getJobs()
{
return $this->jobs;
}
}
