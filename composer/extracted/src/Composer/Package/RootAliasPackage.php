<?php











namespace Composer\Package;




class RootAliasPackage extends AliasPackage implements RootPackageInterface
{
public function __construct(RootPackageInterface $aliasOf, $version, $prettyVersion)
{
parent::__construct($aliasOf, $version, $prettyVersion);
}




public function getAliases()
{
return $this->aliasOf->getAliases();
}




public function getMinimumStability()
{
return $this->aliasOf->getMinimumStability();
}




public function getStabilityFlags()
{
return $this->aliasOf->getStabilityFlags();
}




public function getReferences()
{
return $this->aliasOf->getReferences();
}




public function getPreferStable()
{
return $this->aliasOf->getPreferStable();
}




public function setRequires(array $require)
{
$this->requires = $this->replaceSelfVersionDependencies($require, 'requires');

$this->aliasOf->setRequires($require);
}




public function setDevRequires(array $devRequire)
{
$this->devRequires = $this->replaceSelfVersionDependencies($devRequire, 'devRequires');

$this->aliasOf->setDevRequires($devRequire);
}




public function setConflicts(array $conflicts)
{
$this->conflicts = $this->replaceSelfVersionDependencies($conflicts, 'conflicts');
$this->aliasOf->setConflicts($conflicts);
}




public function setProvides(array $provides)
{
$this->provides = $this->replaceSelfVersionDependencies($provides, 'provides');
$this->aliasOf->setProvides($provides);
}




public function setReplaces(array $replaces)
{
$this->replaces = $this->replaceSelfVersionDependencies($replaces, 'replaces');
$this->aliasOf->setReplaces($replaces);
}




public function setRepositories($repositories)
{
$this->aliasOf->setRepositories($repositories);
}




public function setAutoload(array $autoload)
{
$this->aliasOf->setAutoload($autoload);
}




public function setDevAutoload(array $devAutoload)
{
$this->aliasOf->setDevAutoload($devAutoload);
}




public function setStabilityFlags(array $stabilityFlags)
{
$this->aliasOf->setStabilityFlags($stabilityFlags);
}




public function setSuggests(array $suggests)
{
$this->aliasOf->setSuggests($suggests);
}




public function setExtra(array $extra)
{
$this->aliasOf->setExtra($extra);
}

public function __clone()
{
parent::__clone();
$this->aliasOf = clone $this->aliasOf;
}
}
