<?php











namespace Composer\Package\Version;

use Composer\DependencyResolver\Pool;
use Composer\Package\BasePackage;
use Composer\Package\PackageInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Semver\VersionParser as SemverVersionParser;
use Composer\Semver\Semver;
use Composer\Semver\Constraint\Constraint;







class VersionSelector
{
private $pool;

private $parser;

public function __construct(Pool $pool)
{
$this->pool = $pool;
}











public function findBestCandidate($packageName, $targetPackageVersion = null, $targetPhpVersion = null, $preferredStability = 'stable')
{
$constraint = $targetPackageVersion ? $this->getParser()->parseConstraints($targetPackageVersion) : null;
$candidates = $this->pool->whatProvides(strtolower($packageName), $constraint, true);

if ($targetPhpVersion) {
$phpConstraint = new Constraint('==', $this->getParser()->normalize($targetPhpVersion));
$candidates = array_filter($candidates, function ($pkg) use ($phpConstraint) {
$reqs = $pkg->getRequires();

return !isset($reqs['php']) || $reqs['php']->getConstraint()->matches($phpConstraint);
});
}

if (!$candidates) {
return false;
}


 $package = reset($candidates);
$minPriority = BasePackage::$stabilities[$preferredStability];
foreach ($candidates as $candidate) {
$candidatePriority = $candidate->getStabilityPriority();
$currentPriority = $package->getStabilityPriority();


 if ($minPriority < $candidatePriority && $currentPriority < $candidatePriority) {
continue;
}

 if ($minPriority >= $candidatePriority && $minPriority < $currentPriority) {
$package = $candidate;
continue;
}


 if (version_compare($package->getVersion(), $candidate->getVersion(), '<')) {
$package = $candidate;
}
}

return $package;
}
















public function findRecommendedRequireVersion(PackageInterface $package)
{
$version = $package->getVersion();
if (!$package->isDev()) {
return $this->transformVersion($version, $package->getPrettyVersion(), $package->getStability());
}

$loader = new ArrayLoader($this->getParser());
$dumper = new ArrayDumper();
$extra = $loader->getBranchAlias($dumper->dump($package));
if ($extra) {
$extra = preg_replace('{^(\d+\.\d+\.\d+)(\.9999999)-dev$}', '$1.0', $extra, -1, $count);
if ($count) {
$extra = str_replace('.9999999', '.0', $extra);

return $this->transformVersion($extra, $extra, 'dev');
}
}

return $package->getPrettyVersion();
}

private function transformVersion($version, $prettyVersion, $stability)
{

 
 $semanticVersionParts = explode('.', $version);


 if (count($semanticVersionParts) == 4 && preg_match('{^0\D?}', $semanticVersionParts[3])) {

 if ($semanticVersionParts[0] === '0') {
unset($semanticVersionParts[3]);
} else {
unset($semanticVersionParts[2], $semanticVersionParts[3]);
}
$version = implode('.', $semanticVersionParts);
} else {
return $prettyVersion;
}


 if ($stability != 'stable') {
$version .= '@'.$stability;
}


 return '^' . $version;
}

private function getParser()
{
if ($this->parser === null) {
$this->parser = new SemverVersionParser();
}

return $this->parser;
}
}
