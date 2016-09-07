<?php











namespace Composer\EventDispatcher;

use Composer\DependencyResolver\PolicyInterface;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\Installer\InstallerEvent;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\Repository\CompositeRepository;
use Composer\Script;
use Composer\Script\CommandEvent;
use Composer\Script\PackageEvent;
use Composer\Util\ProcessExecutor;














class EventDispatcher
{
protected $composer;
protected $io;
protected $loader;
protected $process;
protected $listeners;
private $eventStack;








public function __construct(Composer $composer, IOInterface $io, ProcessExecutor $process = null)
{
$this->composer = $composer;
$this->io = $io;
$this->process = $process ?: new ProcessExecutor($io);
$this->eventStack = array();
}









public function dispatch($eventName, Event $event = null)
{
if (null == $event) {
$event = new Event($eventName);
}

return $this->doDispatch($event);
}











public function dispatchScript($eventName, $devMode = false, $additionalArgs = array(), $flags = array())
{
return $this->doDispatch(new Script\Event($eventName, $this->composer, $this->io, $devMode, $additionalArgs, $flags));
}
















public function dispatchPackageEvent($eventName, $devMode, PolicyInterface $policy, Pool $pool, CompositeRepository $installedRepo, Request $request, array $operations, OperationInterface $operation)
{
return $this->doDispatch(new PackageEvent($eventName, $this->composer, $this->io, $devMode, $policy, $pool, $installedRepo, $request, $operations, $operation));
}















public function dispatchInstallerEvent($eventName, $devMode, PolicyInterface $policy, Pool $pool, CompositeRepository $installedRepo, Request $request, array $operations = array())
{
return $this->doDispatch(new InstallerEvent($eventName, $this->composer, $this->io, $devMode, $policy, $pool, $installedRepo, $request, $operations));
}











protected function doDispatch(Event $event)
{

 $binDir = $this->composer->getConfig()->get('bin-dir');
if (is_dir($binDir)) {
$binDir = realpath($binDir);
if (isset($_SERVER['PATH']) && !preg_match('{(^|'.PATH_SEPARATOR.')'.preg_quote($binDir).'($|'.PATH_SEPARATOR.')}', $_SERVER['PATH'])) {
$_SERVER['PATH'] = $binDir.PATH_SEPARATOR.getenv('PATH');
putenv('PATH='.$_SERVER['PATH']);
}
}

$listeners = $this->getListeners($event);

$this->pushEvent($event);

$return = 0;
foreach ($listeners as $callable) {
if (!is_string($callable) && is_callable($callable)) {
$event = $this->checkListenerExpectedEvent($callable, $event);
$return = false === call_user_func($callable, $event) ? 1 : 0;
} elseif ($this->isComposerScript($callable)) {
$this->io->writeError(sprintf('> %s: %s', $event->getName(), $callable), true, IOInterface::VERBOSE);
$scriptName = substr($callable, 1);
$args = $event->getArguments();
$flags = $event->getFlags();
$return = $this->dispatch($scriptName, new Script\Event($scriptName, $event->getComposer(), $event->getIO(), $event->isDevMode(), $args, $flags));
} elseif ($this->isPhpScript($callable)) {
$className = substr($callable, 0, strpos($callable, '::'));
$methodName = substr($callable, strpos($callable, '::') + 2);

if (!class_exists($className)) {
$this->io->writeError('<warning>Class '.$className.' is not autoloadable, can not call '.$event->getName().' script</warning>');
continue;
}
if (!is_callable($callable)) {
$this->io->writeError('<warning>Method '.$callable.' is not callable, can not call '.$event->getName().' script</warning>');
continue;
}

try {
$return = false === $this->executeEventPhpScript($className, $methodName, $event) ? 1 : 0;
} catch (\Exception $e) {
$message = "Script %s handling the %s event terminated with an exception";
$this->io->writeError('<error>'.sprintf($message, $callable, $event->getName()).'</error>');
throw $e;
}
} else {
$args = implode(' ', array_map(array('Composer\Util\ProcessExecutor', 'escape'), $event->getArguments()));
$exec = $callable . ($args === '' ? '' : ' '.$args);
if ($this->io->isVerbose()) {
$this->io->writeError(sprintf('> %s: %s', $event->getName(), $exec));
} else {
$this->io->writeError(sprintf('> %s', $exec));
}
if (0 !== ($exitCode = $this->process->execute($exec))) {
$this->io->writeError(sprintf('<error>Script %s handling the %s event returned with an error</error>', $callable, $event->getName()));

throw new \RuntimeException('Error Output: '.$this->process->getErrorOutput(), $exitCode);
}
}

if ($event->isPropagationStopped()) {
break;
}
}

$this->popEvent();

return $return;
}






protected function executeEventPhpScript($className, $methodName, Event $event)
{
$event = $this->checkListenerExpectedEvent(array($className, $methodName), $event);

if ($this->io->isVerbose()) {
$this->io->writeError(sprintf('> %s: %s::%s', $event->getName(), $className, $methodName));
} else {
$this->io->writeError(sprintf('> %s::%s', $className, $methodName));
}

return $className::$methodName($event);
}






protected function checkListenerExpectedEvent($target, Event $event)
{
try {
$reflected = new \ReflectionParameter($target, 0);
} catch (\Exception $e) {
return $event;
}

$typehint = $reflected->getClass();

if (!$typehint instanceof \ReflectionClass) {
return $event;
}

$expected = $typehint->getName();


 if (!$event instanceof $expected && $expected === 'Composer\Script\CommandEvent') {
$event = new \Composer\Script\CommandEvent(
$event->getName(), $event->getComposer(), $event->getIO(), $event->isDevMode(), $event->getArguments()
);
}
if (!$event instanceof $expected && $expected === 'Composer\Script\PackageEvent') {
$event = new \Composer\Script\PackageEvent(
$event->getName(), $event->getComposer(), $event->getIO(), $event->isDevMode(),
$event->getPolicy(), $event->getPool(), $event->getInstalledRepo(), $event->getRequest(),
$event->getOperations(), $event->getOperation()
);
}
if (!$event instanceof $expected && $expected === 'Composer\Script\Event') {
$event = new \Composer\Script\Event(
$event->getName(), $event->getComposer(), $event->getIO(), $event->isDevMode(),
$event->getArguments(), $event->getFlags()
);
}

return $event;
}








protected function addListener($eventName, $listener, $priority = 0)
{
$this->listeners[$eventName][$priority][] = $listener;
}








public function addSubscriber(EventSubscriberInterface $subscriber)
{
foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
if (is_string($params)) {
$this->addListener($eventName, array($subscriber, $params));
} elseif (is_string($params[0])) {
$this->addListener($eventName, array($subscriber, $params[0]), isset($params[1]) ? $params[1] : 0);
} else {
foreach ($params as $listener) {
$this->addListener($eventName, array($subscriber, $listener[0]), isset($listener[1]) ? $listener[1] : 0);
}
}
}
}







protected function getListeners(Event $event)
{
$scriptListeners = $this->getScriptListeners($event);

if (!isset($this->listeners[$event->getName()][0])) {
$this->listeners[$event->getName()][0] = array();
}
krsort($this->listeners[$event->getName()]);

$listeners = $this->listeners;
$listeners[$event->getName()][0] = array_merge($listeners[$event->getName()][0], $scriptListeners);

return call_user_func_array('array_merge', $listeners[$event->getName()]);
}







public function hasEventListeners(Event $event)
{
$listeners = $this->getListeners($event);

return count($listeners) > 0;
}







protected function getScriptListeners(Event $event)
{
$package = $this->composer->getPackage();
$scripts = $package->getScripts();

if (empty($scripts[$event->getName()])) {
return array();
}

if ($this->loader) {
$this->loader->unregister();
}

$generator = $this->composer->getAutoloadGenerator();
$packages = $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
$packageMap = $generator->buildPackageMap($this->composer->getInstallationManager(), $package, $packages);
$map = $generator->parseAutoloads($packageMap, $package);
$this->loader = $generator->createLoader($map);
$this->loader->register();

return $scripts[$event->getName()];
}







protected function isPhpScript($callable)
{
return false === strpos($callable, ' ') && false !== strpos($callable, '::');
}







protected function isComposerScript($callable)
{
return '@' === substr($callable, 0, 1);
}








protected function pushEvent(Event $event)
{
$eventName = $event->getName();
if (in_array($eventName, $this->eventStack)) {
throw new \RuntimeException(sprintf("Circular call to script handler '%s' detected", $eventName));
}

return array_push($this->eventStack, $eventName);
}






protected function popEvent()
{
return array_pop($this->eventStack);
}
}
