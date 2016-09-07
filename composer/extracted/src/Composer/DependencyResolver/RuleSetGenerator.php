<?php











namespace Composer\DependencyResolver;

use Composer\Package\PackageInterface;
use Composer\Package\AliasPackage;
use Composer\Repository\PlatformRepository;




class RuleSetGenerator
{
protected $policy;
protected $pool;
protected $rules;
protected $jobs;
protected $installedMap;
protected $whitelistedMap;
protected $addedMap;

public function __construct(PolicyInterface $policy, Pool $pool)
{
$this->policy = $policy;
$this->pool = $pool;
}















protected function createRequireRule(PackageInterface $package, array $providers, $reason, $reasonData = null)
{
$literals = array(-$package->id);

foreach ($providers as $provider) {

 if ($provider === $package) {
return null;
}
$literals[] = $provider->id;
}

return new Rule($literals, $reason, $reasonData);
}













protected function createInstallOneOfRule(array $packages, $reason, $job)
{
$literals = array();
foreach ($packages as $package) {
$literals[] = $package->id;
}

return new Rule($literals, $reason, $job['packageName'], $job);
}












protected function createRemoveRule(PackageInterface $package, $reason, $job)
{
return new Rule(array(-$package->id), $reason, $job['packageName'], $job);
}















protected function createConflictRule(PackageInterface $issuer, PackageInterface $provider, $reason, $reasonData = null)
{

 if ($issuer === $provider) {
return null;
}

return new Rule(array(-$issuer->id, -$provider->id), $reason, $reasonData);
}










private function addRule($type, Rule $newRule = null)
{
if (!$newRule || $this->rules->containsEqual($newRule)) {
return;
}

$this->rules->add($newRule, $type);
}

protected function whitelistFromPackage(PackageInterface $package)
{
$workQueue = new \SplQueue;
$workQueue->enqueue($package);

while (!$workQueue->isEmpty()) {
$package = $workQueue->dequeue();
if (isset($this->whitelistedMap[$package->id])) {
continue;
}

$this->whitelistedMap[$package->id] = true;

foreach ($package->getRequires() as $link) {
$possibleRequires = $this->pool->whatProvides($link->getTarget(), $link->getConstraint(), true);

foreach ($possibleRequires as $require) {
$workQueue->enqueue($require);
}
}

$obsoleteProviders = $this->pool->whatProvides($package->getName(), null, true);

foreach ($obsoleteProviders as $provider) {
if ($provider === $package) {
continue;
}

if (($package instanceof AliasPackage) && $package->getAliasOf() === $provider) {
$workQueue->enqueue($provider);
}
}
}
}

protected function addRulesForPackage(PackageInterface $package, $ignorePlatformReqs)
{
$workQueue = new \SplQueue;
$workQueue->enqueue($package);

while (!$workQueue->isEmpty()) {
$package = $workQueue->dequeue();
if (isset($this->addedMap[$package->id])) {
continue;
}

$this->addedMap[$package->id] = true;

foreach ($package->getRequires() as $link) {
if ($ignorePlatformReqs && preg_match(PlatformRepository::PLATFORM_PACKAGE_REGEX, $link->getTarget())) {
continue;
}

$possibleRequires = $this->pool->whatProvides($link->getTarget(), $link->getConstraint());

$this->addRule(RuleSet::TYPE_PACKAGE, $rule = $this->createRequireRule($package, $possibleRequires, Rule::RULE_PACKAGE_REQUIRES, $link));

foreach ($possibleRequires as $require) {
$workQueue->enqueue($require);
}
}

foreach ($package->getConflicts() as $link) {
$possibleConflicts = $this->pool->whatProvides($link->getTarget(), $link->getConstraint());

foreach ($possibleConflicts as $conflict) {
$this->addRule(RuleSet::TYPE_PACKAGE, $this->createConflictRule($package, $conflict, Rule::RULE_PACKAGE_CONFLICT, $link));
}
}


 $isInstalled = (isset($this->installedMap[$package->id]));

foreach ($package->getReplaces() as $link) {
$obsoleteProviders = $this->pool->whatProvides($link->getTarget(), $link->getConstraint());

foreach ($obsoleteProviders as $provider) {
if ($provider === $package) {
continue;
}

if (!$this->obsoleteImpossibleForAlias($package, $provider)) {
$reason = ($isInstalled) ? Rule::RULE_INSTALLED_PACKAGE_OBSOLETES : Rule::RULE_PACKAGE_OBSOLETES;
$this->addRule(RuleSet::TYPE_PACKAGE, $this->createConflictRule($package, $provider, $reason, $link));
}
}
}

$obsoleteProviders = $this->pool->whatProvides($package->getName(), null);

foreach ($obsoleteProviders as $provider) {
if ($provider === $package) {
continue;
}

if (($package instanceof AliasPackage) && $package->getAliasOf() === $provider) {
$this->addRule(RuleSet::TYPE_PACKAGE, $rule = $this->createRequireRule($package, array($provider), Rule::RULE_PACKAGE_ALIAS, $package));
} elseif (!$this->obsoleteImpossibleForAlias($package, $provider)) {
$reason = ($package->getName() == $provider->getName()) ? Rule::RULE_PACKAGE_SAME_NAME : Rule::RULE_PACKAGE_IMPLICIT_OBSOLETES;
$this->addRule(RuleSet::TYPE_PACKAGE, $rule = $this->createConflictRule($package, $provider, $reason, $package));
}
}
}
}

protected function obsoleteImpossibleForAlias($package, $provider)
{
$packageIsAlias = $package instanceof AliasPackage;
$providerIsAlias = $provider instanceof AliasPackage;

$impossible = (
($packageIsAlias && $package->getAliasOf() === $provider) ||
($providerIsAlias && $provider->getAliasOf() === $package) ||
($packageIsAlias && $providerIsAlias && $provider->getAliasOf() === $package->getAliasOf())
);

return $impossible;
}

protected function whitelistFromJobs()
{
foreach ($this->jobs as $job) {
switch ($job['cmd']) {
case 'install':
$packages = $this->pool->whatProvides($job['packageName'], $job['constraint'], true);
foreach ($packages as $package) {
$this->whitelistFromPackage($package);
}
break;
}
}
}

protected function addRulesForJobs($ignorePlatformReqs)
{
foreach ($this->jobs as $job) {
switch ($job['cmd']) {
case 'install':
if (!$job['fixed'] && $ignorePlatformReqs && preg_match(PlatformRepository::PLATFORM_PACKAGE_REGEX, $job['packageName'])) {
continue;
}

$packages = $this->pool->whatProvides($job['packageName'], $job['constraint']);
if ($packages) {
foreach ($packages as $package) {
if (!isset($this->installedMap[$package->id])) {
$this->addRulesForPackage($package, $ignorePlatformReqs);
}
}

$rule = $this->createInstallOneOfRule($packages, Rule::RULE_JOB_INSTALL, $job);
$this->addRule(RuleSet::TYPE_JOB, $rule);
}
break;
case 'remove':

 
 $packages = $this->pool->whatProvides($job['packageName'], $job['constraint']);
foreach ($packages as $package) {
$rule = $this->createRemoveRule($package, Rule::RULE_JOB_REMOVE, $job);
$this->addRule(RuleSet::TYPE_JOB, $rule);
}
break;
}
}
}

public function getRulesFor($jobs, $installedMap, $ignorePlatformReqs = false)
{
$this->jobs = $jobs;
$this->rules = new RuleSet;
$this->installedMap = $installedMap;

$this->whitelistedMap = array();
foreach ($this->installedMap as $package) {
$this->whitelistFromPackage($package);
}
$this->whitelistFromJobs();

$this->pool->setWhitelist($this->whitelistedMap);

$this->addedMap = array();
foreach ($this->installedMap as $package) {
$this->addRulesForPackage($package, $ignorePlatformReqs);
}

$this->addRulesForJobs($ignorePlatformReqs);

return $this->rules;
}
}
