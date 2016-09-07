<?php











namespace Composer\Package;

use Composer\Json\JsonFile;
use Composer\Installer\InstallationManager;
use Composer\Repository\RepositoryManager;
use Composer\Util\ProcessExecutor;
use Composer\Repository\ArrayRepository;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Package\Loader\ArrayLoader;
use Composer\Util\Git as GitUtil;
use Composer\IO\IOInterface;
use Seld\JsonLint\ParsingException;







class Locker
{
private $lockFile;
private $repositoryManager;
private $installationManager;
private $hash;
private $contentHash;
private $loader;
private $dumper;
private $process;
private $lockDataCache;










public function __construct(IOInterface $io, JsonFile $lockFile, RepositoryManager $repositoryManager, InstallationManager $installationManager, $composerFileContents)
{
$this->lockFile = $lockFile;
$this->repositoryManager = $repositoryManager;
$this->installationManager = $installationManager;
$this->hash = md5($composerFileContents);
$this->contentHash = self::getContentHash($composerFileContents);
$this->loader = new ArrayLoader(null, true);
$this->dumper = new ArrayDumper();
$this->process = new ProcessExecutor($io);
}








public static function getContentHash($composerFileContents)
{
$content = json_decode($composerFileContents, true);

$relevantKeys = array(
'name',
'version',
'require',
'require-dev',
'conflict',
'replace',
'provide',
'minimum-stability',
'prefer-stable',
'repositories',
'extra',
);

$relevantContent = array();

foreach (array_intersect($relevantKeys, array_keys($content)) as $key) {
$relevantContent[$key] = $content[$key];
}
if (isset($content['config']['platform'])) {
$relevantContent['config']['platform'] = $content['config']['platform'];
}

ksort($relevantContent);

return md5(json_encode($relevantContent));
}






public function isLocked()
{
if (!$this->lockFile->exists()) {
return false;
}

$data = $this->getLockData();

return isset($data['packages']);
}






public function isFresh()
{
$lock = $this->lockFile->read();

if (!empty($lock['content-hash'])) {

 return $this->contentHash === $lock['content-hash'];
}

return $this->hash === $lock['hash'];
}








public function getLockedRepository($withDevReqs = false)
{
$lockData = $this->getLockData();
$packages = new ArrayRepository();

$lockedPackages = $lockData['packages'];
if ($withDevReqs) {
if (isset($lockData['packages-dev'])) {
$lockedPackages = array_merge($lockedPackages, $lockData['packages-dev']);
} else {
throw new \RuntimeException('The lock file does not contain require-dev information, run install with the --no-dev option or run update to install those packages.');
}
}

if (empty($lockedPackages)) {
return $packages;
}

if (isset($lockedPackages[0]['name'])) {
foreach ($lockedPackages as $info) {
$packages->addPackage($this->loader->load($info));
}

return $packages;
}

throw new \RuntimeException('Your composer.lock was created before 2012-09-15, and is not supported anymore. Run "composer update" to generate a new one.');
}







public function getPlatformRequirements($withDevReqs = false)
{
$lockData = $this->getLockData();
$requirements = array();

if (!empty($lockData['platform'])) {
$requirements = $this->loader->parseLinks(
'__ROOT__',
'1.0.0',
'requires',
isset($lockData['platform']) ? $lockData['platform'] : array()
);
}

if ($withDevReqs && !empty($lockData['platform-dev'])) {
$devRequirements = $this->loader->parseLinks(
'__ROOT__',
'1.0.0',
'requires',
isset($lockData['platform-dev']) ? $lockData['platform-dev'] : array()
);

$requirements = array_merge($requirements, $devRequirements);
}

return $requirements;
}

public function getMinimumStability()
{
$lockData = $this->getLockData();

return isset($lockData['minimum-stability']) ? $lockData['minimum-stability'] : 'stable';
}

public function getStabilityFlags()
{
$lockData = $this->getLockData();

return isset($lockData['stability-flags']) ? $lockData['stability-flags'] : array();
}

public function getPreferStable()
{
$lockData = $this->getLockData();


 
 return isset($lockData['prefer-stable']) ? $lockData['prefer-stable'] : null;
}

public function getPreferLowest()
{
$lockData = $this->getLockData();


 
 return isset($lockData['prefer-lowest']) ? $lockData['prefer-lowest'] : null;
}

public function getPlatformOverrides()
{
$lockData = $this->getLockData();

return isset($lockData['platform-overrides']) ? $lockData['platform-overrides'] : array();
}

public function getAliases()
{
$lockData = $this->getLockData();

return isset($lockData['aliases']) ? $lockData['aliases'] : array();
}

public function getLockData()
{
if (null !== $this->lockDataCache) {
return $this->lockDataCache;
}

if (!$this->lockFile->exists()) {
throw new \LogicException('No lockfile found. Unable to read locked packages');
}

return $this->lockDataCache = $this->lockFile->read();
}

















public function setLockData(array $packages, $devPackages, array $platformReqs, $platformDevReqs, array $aliases, $minimumStability, array $stabilityFlags, $preferStable, $preferLowest, array $platformOverrides)
{
$lock = array(
'_readme' => array('This file locks the dependencies of your project to a known state',
'Read more about it at https://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file',
'This file is @gener'.'ated automatically', ),
'hash' => $this->hash,
'content-hash' => $this->contentHash,
'packages' => null,
'packages-dev' => null,
'aliases' => array(),
'minimum-stability' => $minimumStability,
'stability-flags' => $stabilityFlags,
'prefer-stable' => $preferStable,
'prefer-lowest' => $preferLowest,
);

foreach ($aliases as $package => $versions) {
foreach ($versions as $version => $alias) {
$lock['aliases'][] = array(
'alias' => $alias['alias'],
'alias_normalized' => $alias['alias_normalized'],
'version' => $version,
'package' => $package,
);
}
}

$lock['packages'] = $this->lockPackages($packages);
if (null !== $devPackages) {
$lock['packages-dev'] = $this->lockPackages($devPackages);
}

$lock['platform'] = $platformReqs;
$lock['platform-dev'] = $platformDevReqs;
if ($platformOverrides) {
$lock['platform-overrides'] = $platformOverrides;
}

if (empty($lock['packages']) && empty($lock['packages-dev']) && empty($lock['platform']) && empty($lock['platform-dev'])) {
if ($this->lockFile->exists()) {
unlink($this->lockFile->getPath());
}

return false;
}

try {
$isLocked = $this->isLocked();
} catch (ParsingException $e) {
$isLocked = false;
}
if (!$isLocked || $lock !== $this->getLockData()) {
$this->lockFile->write($lock);
$this->lockDataCache = null;

return true;
}

return false;
}

private function lockPackages(array $packages)
{
$locked = array();

foreach ($packages as $package) {
if ($package instanceof AliasPackage) {
continue;
}

$name = $package->getPrettyName();
$version = $package->getPrettyVersion();

if (!$name || !$version) {
throw new \LogicException(sprintf(
'Package "%s" has no version or name and can not be locked', $package
));
}

$spec = $this->dumper->dump($package);
unset($spec['version_normalized']);


 $time = isset($spec['time']) ? $spec['time'] : null;
unset($spec['time']);
if ($package->isDev() && $package->getInstallationSource() === 'source') {

 $time = $this->getPackageTime($package) ?: $time;
}
if (null !== $time) {
$spec['time'] = $time;
}

unset($spec['installation-source']);

$locked[] = $spec;
}

usort($locked, function ($a, $b) {
$comparison = strcmp($a['name'], $b['name']);

if (0 !== $comparison) {
return $comparison;
}


 return strcmp($a['version'], $b['version']);
});

return $locked;
}







private function getPackageTime(PackageInterface $package)
{
if (!function_exists('proc_open')) {
return null;
}

$path = realpath($this->installationManager->getInstallPath($package));
$sourceType = $package->getSourceType();
$datetime = null;

if ($path && in_array($sourceType, array('git', 'hg'))) {
$sourceRef = $package->getSourceReference() ?: $package->getDistReference();
switch ($sourceType) {
case 'git':
GitUtil::cleanEnv();

if (0 === $this->process->execute('git log -n1 --pretty=%ct '.ProcessExecutor::escape($sourceRef), $output, $path) && preg_match('{^\s*\d+\s*$}', $output)) {
$datetime = new \DateTime('@'.trim($output), new \DateTimeZone('UTC'));
}
break;

case 'hg':
if (0 === $this->process->execute('hg log --template "{date|hgdate}" -r '.ProcessExecutor::escape($sourceRef), $output, $path) && preg_match('{^\s*(\d+)\s*}', $output, $match)) {
$datetime = new \DateTime('@'.$match[1], new \DateTimeZone('UTC'));
}
break;
}
}

return $datetime ? $datetime->format('Y-m-d H:i:s') : null;
}
}
