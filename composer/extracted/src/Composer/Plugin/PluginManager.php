<?php











namespace Composer\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Semver\VersionParser;
use Composer\Repository\RepositoryInterface;
use Composer\Package\AliasPackage;
use Composer\Package\PackageInterface;
use Composer\Package\Link;
use Composer\Semver\Constraint\Constraint;
use Composer\DependencyResolver\Pool;
use Composer\Plugin\Capability\Capability;







class PluginManager
{
protected $composer;
protected $io;
protected $globalComposer;
protected $versionParser;
protected $disablePlugins = false;

protected $plugins = array();
protected $registeredPlugins = array();

private static $classCounter = 0;









public function __construct(IOInterface $io, Composer $composer, Composer $globalComposer = null, $disablePlugins = false)
{
$this->io = $io;
$this->composer = $composer;
$this->globalComposer = $globalComposer;
$this->versionParser = new VersionParser();
$this->disablePlugins = $disablePlugins;
}




public function loadInstalledPlugins()
{
if ($this->disablePlugins) {
return;
}

$repo = $this->composer->getRepositoryManager()->getLocalRepository();
$globalRepo = $this->globalComposer ? $this->globalComposer->getRepositoryManager()->getLocalRepository() : null;
if ($repo) {
$this->loadRepository($repo);
}
if ($globalRepo) {
$this->loadRepository($globalRepo);
}
}






public function getPlugins()
{
return $this->plugins;
}












public function registerPackage(PackageInterface $package, $failOnMissingClasses = false)
{
if ($this->disablePlugins) {
return;
}

if ($package->getType() === 'composer-plugin') {
$requiresComposer = null;
foreach ($package->getRequires() as $link) { 
if ('composer-plugin-api' === $link->getTarget()) {
$requiresComposer = $link->getConstraint();
break;
}
}

if (!$requiresComposer) {
throw new \RuntimeException("Plugin ".$package->getName()." is missing a require statement for a version of the composer-plugin-api package.");
}

$currentPluginApiVersion = $this->getPluginApiVersion();
$currentPluginApiConstraint = new Constraint('==', $this->versionParser->normalize($currentPluginApiVersion));

if ($requiresComposer->getPrettyString() === '1.0.0' && $this->getPluginApiVersion() === '1.0.0') {
$this->io->writeError('<warning>The "' . $package->getName() . '" plugin requires composer-plugin-api 1.0.0, this *WILL* break in the future and it should be fixed ASAP (require ^1.0 for example).</warning>');
} elseif (!$requiresComposer->matches($currentPluginApiConstraint)) {
$this->io->writeError('<warning>The "' . $package->getName() . '" plugin was skipped because it requires a Plugin API version ("' . $requiresComposer->getPrettyString() . '") that does not match your Composer installation ("' . $currentPluginApiVersion . '"). You may need to run composer update with the "--no-plugins" option.</warning>');

return;
}
}

$oldInstallerPlugin = ($package->getType() === 'composer-installer');

if (in_array($package->getName(), $this->registeredPlugins)) {
return;
}

$extra = $package->getExtra();
if (empty($extra['class'])) {
throw new \UnexpectedValueException('Error while installing '.$package->getPrettyName().', composer-plugin packages should have a class defined in their extra key to be usable.');
}
$classes = is_array($extra['class']) ? $extra['class'] : array($extra['class']);

$localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
$globalRepo = $this->globalComposer ? $this->globalComposer->getRepositoryManager()->getLocalRepository() : null;

$pool = new Pool('dev');
$pool->addRepository($localRepo);
if ($globalRepo) {
$pool->addRepository($globalRepo);
}

$autoloadPackages = array($package->getName() => $package);
$autoloadPackages = $this->collectDependencies($pool, $autoloadPackages, $package);

$generator = $this->composer->getAutoloadGenerator();
$autoloads = array();
foreach ($autoloadPackages as $autoloadPackage) {
$downloadPath = $this->getInstallPath($autoloadPackage, ($globalRepo && $globalRepo->hasPackage($autoloadPackage)));
$autoloads[] = array($autoloadPackage, $downloadPath);
}

$map = $generator->parseAutoloads($autoloads, new Package('dummy', '1.0.0.0', '1.0.0'));
$classLoader = $generator->createLoader($map);
$classLoader->register();

foreach ($classes as $class) {
if (class_exists($class, false)) {
$code = file_get_contents($classLoader->findFile($class));
$code = preg_replace('{^((?:final\s+)?(?:\s*))class\s+(\S+)}mi', '$1class $2_composer_tmp'.self::$classCounter, $code);
eval('?>'.$code);
$class .= '_composer_tmp'.self::$classCounter;
self::$classCounter++;
}

if ($oldInstallerPlugin) {
$installer = new $class($this->io, $this->composer);
$this->composer->getInstallationManager()->addInstaller($installer);
} elseif (class_exists($class)) {
$plugin = new $class();
$this->addPlugin($plugin);
$this->registeredPlugins[] = $package->getName();
} elseif ($failOnMissingClasses) {
throw new \UnexpectedValueException('Plugin '.$package->getName().' could not be initialized, class not found: '.$class);
}
}
}






protected function getPluginApiVersion()
{
return PluginInterface::PLUGIN_API_VERSION;
}






private function addPlugin(PluginInterface $plugin)
{
$this->io->writeError('Loading plugin '.get_class($plugin), true, IOInterface::DEBUG);
$this->plugins[] = $plugin;
$plugin->activate($this->composer, $this->io);

if ($plugin instanceof EventSubscriberInterface) {
$this->composer->getEventDispatcher()->addSubscriber($plugin);
}
}












private function loadRepository(RepositoryInterface $repo)
{
foreach ($repo->getPackages() as $package) { 
if ($package instanceof AliasPackage) {
continue;
}
if ('composer-plugin' === $package->getType()) {
$this->registerPackage($package);

 } elseif ('composer-installer' === $package->getType()) {
$this->registerPackage($package);
}
}
}










private function collectDependencies(Pool $pool, array $collected, PackageInterface $package)
{
$requires = array_merge(
$package->getRequires(),
$package->getDevRequires()
);

foreach ($requires as $requireLink) {
$requiredPackage = $this->lookupInstalledPackage($pool, $requireLink);
if ($requiredPackage && !isset($collected[$requiredPackage->getName()])) {
$collected[$requiredPackage->getName()] = $requiredPackage;
$collected = $this->collectDependencies($pool, $collected, $requiredPackage);
}
}

return $collected;
}











private function lookupInstalledPackage(Pool $pool, Link $link)
{
$packages = $pool->whatProvides($link->getTarget(), $link->getConstraint());

return (!empty($packages)) ? $packages[0] : null;
}









private function getInstallPath(PackageInterface $package, $global = false)
{
if (!$global) {
return $this->composer->getInstallationManager()->getInstallPath($package);
}

return $this->globalComposer->getInstallationManager()->getInstallPath($package);
}







protected function getCapabilityImplementationClassName(PluginInterface $plugin, $capability)
{
if (!($plugin instanceof Capable)) {
return null;
}

$capabilities = (array) $plugin->getCapabilities();

if (!empty($capabilities[$capability]) && is_string($capabilities[$capability]) && trim($capabilities[$capability])) {
return trim($capabilities[$capability]);
}

if (
array_key_exists($capability, $capabilities)
&& (empty($capabilities[$capability]) || !is_string($capabilities[$capability]) || !trim($capabilities[$capability]))
) {
throw new \UnexpectedValueException('Plugin '.get_class($plugin).' provided invalid capability class name(s), got '.var_export($capabilities[$capability], 1));
}
}









public function getPluginCapability(PluginInterface $plugin, $capabilityClassName, array $ctorArgs = array())
{
if ($capabilityClass = $this->getCapabilityImplementationClassName($plugin, $capabilityClassName)) {
if (!class_exists($capabilityClass)) {
throw new \RuntimeException("Cannot instantiate Capability, as class $capabilityClass from plugin ".get_class($plugin)." does not exist.");
}

$capabilityObj = new $capabilityClass($ctorArgs);


 if (!$capabilityObj instanceof Capability || !$capabilityObj instanceof $capabilityClassName) {
throw new \RuntimeException(
'Class ' . $capabilityClass . ' must implement both Composer\Plugin\Capability\Capability and '. $capabilityClassName . '.'
);
}

return $capabilityObj;
}
}
}
