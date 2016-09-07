<?php











namespace Composer\Console;

use Composer\Util\Platform;
use Composer\Util\Silencer;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Composer\Command;
use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\IO\ConsoleIO;
use Composer\Json\JsonValidationException;
use Composer\Util\ErrorHandler;








class Application extends BaseApplication
{



protected $composer;




protected $io;

private static $logo = '   ______
  / ____/___  ____ ___  ____  ____  ________  _____
 / /   / __ \/ __ `__ \/ __ \/ __ \/ ___/ _ \/ ___/
/ /___/ /_/ / / / / / / /_/ / /_/ (__  )  __/ /
\____/\____/_/ /_/ /_/ .___/\____/____/\___/_/
                    /_/
';

public function __construct()
{
static $shutdownRegistered = false;

if (function_exists('ini_set') && extension_loaded('xdebug')) {
ini_set('xdebug.show_exception_trace', false);
ini_set('xdebug.scream', false);
}

if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
date_default_timezone_set(Silencer::call('date_default_timezone_get'));
}

if (!$shutdownRegistered) {
$shutdownRegistered = true;

register_shutdown_function(function () {
$lastError = error_get_last();

if ($lastError && $lastError['message'] &&
(strpos($lastError['message'], 'Allowed memory') !== false  ||
strpos($lastError['message'], 'exceeded memory') !== false )) {
echo "\n". 'Check https://getcomposer.org/doc/articles/troubleshooting.md#memory-limit-errors for more info on how to handle out of memory errors.';
}
});
}

parent::__construct('Composer', Composer::VERSION);
}




public function run(InputInterface $input = null, OutputInterface $output = null)
{
if (null === $output) {
$styles = Factory::createAdditionalStyles();
$formatter = new OutputFormatter(null, $styles);
$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_NORMAL, null, $formatter);
}

return parent::run($input, $output);
}




public function doRun(InputInterface $input, OutputInterface $output)
{
$io = $this->io = new ConsoleIO($input, $output, $this->getHelperSet());
ErrorHandler::register($io);


 $commandName = '';
if ($name = $this->getCommandName($input)) {
try {
$commandName = $this->find($name)->getName();
} catch (\InvalidArgumentException $e) {
}
}

if ($commandName !== 'global') {
if (PHP_VERSION_ID < 50302) {
$io->writeError('<warning>Composer only officially supports PHP 5.3.2 and above, you will most likely encounter problems with your PHP '.PHP_VERSION.', upgrading is strongly recommended.</warning>');
}

if (extension_loaded('xdebug') && !getenv('COMPOSER_DISABLE_XDEBUG_WARN')) {
$io->writeError('<warning>You are running composer with xdebug enabled. This has a major impact on runtime performance. See https://getcomposer.org/xdebug</warning>');
}

if (defined('COMPOSER_DEV_WARNING_TIME') && $commandName !== 'self-update' && $commandName !== 'selfupdate' && time() > COMPOSER_DEV_WARNING_TIME) {
$io->writeError(sprintf('<warning>Warning: This development build of composer is over 60 days old. It is recommended to update it by running "%s self-update" to get the latest version.</warning>', $_SERVER['PHP_SELF']));
}

if (getenv('COMPOSER_NO_INTERACTION')) {
$input->setInteractive(false);
}


 if ($newWorkDir = $this->getNewWorkingDir($input)) {
$oldWorkingDir = getcwd();
chdir($newWorkDir);
$io->writeError('Changed CWD to ' . getcwd(), true, IOInterface::DEBUG);
}


 $file = Factory::getComposerFile();
if (is_file($file) && is_readable($file) && is_array($composer = json_decode(file_get_contents($file), true))) {
if (isset($composer['scripts']) && is_array($composer['scripts'])) {
foreach ($composer['scripts'] as $script => $dummy) {
if (!defined('Composer\Script\ScriptEvents::'.str_replace('-', '_', strtoupper($script)))) {
if ($this->has($script)) {
$io->writeError('<warning>A script named '.$script.' would override a native Composer function and has been skipped</warning>');
} else {
$this->add(new Command\ScriptAliasCommand($script));
}
}
}
}
}
}

try {
if ($input->hasParameterOption('--profile')) {
$startTime = microtime(true);
$this->io->enableDebugging($startTime);
}

$result = parent::doRun($input, $output);

if (isset($oldWorkingDir)) {
chdir($oldWorkingDir);
}

if (isset($startTime)) {
$io->writeError('<info>Memory usage: '.round(memory_get_usage() / 1024 / 1024, 2).'MB (peak: '.round(memory_get_peak_usage() / 1024 / 1024, 2).'MB), time: '.round(microtime(true) - $startTime, 2).'s');
}

return $result;
} catch (\Exception $e) {
$this->hintCommonErrors($e);
throw $e;
}
}






private function getNewWorkingDir(InputInterface $input)
{
$workingDir = $input->getParameterOption(array('--working-dir', '-d'));
if (false !== $workingDir && !is_dir($workingDir)) {
throw new \RuntimeException('Invalid working directory specified, '.$workingDir.' does not exist.');
}

return $workingDir;
}




private function hintCommonErrors($exception)
{
$io = $this->getIO();

Silencer::suppress();
try {
$composer = $this->getComposer(false, true);
if ($composer) {
$config = $composer->getConfig();

$minSpaceFree = 1024 * 1024;
if ((($df = disk_free_space($dir = $config->get('home'))) !== false && $df < $minSpaceFree)
|| (($df = disk_free_space($dir = $config->get('vendor-dir'))) !== false && $df < $minSpaceFree)
|| (($df = disk_free_space($dir = sys_get_temp_dir())) !== false && $df < $minSpaceFree)
) {
$io->writeError('<error>The disk hosting '.$dir.' is full, this may be the cause of the following exception</error>', true, IOInterface::QUIET);
}
}
} catch (\Exception $e) {
}
Silencer::restore();

if (Platform::isWindows() && false !== strpos($exception->getMessage(), 'The system cannot find the path specified')) {
$io->writeError('<error>The following exception may be caused by a stale entry in your cmd.exe AutoRun</error>', true, IOInterface::QUIET);
$io->writeError('<error>Check https://getcomposer.org/doc/articles/troubleshooting.md#-the-system-cannot-find-the-path-specified-windows- for details</error>', true, IOInterface::QUIET);
}

if (false !== strpos($exception->getMessage(), 'fork failed - Cannot allocate memory')) {
$io->writeError('<error>The following exception is caused by a lack of memory and not having swap configured</error>', true, IOInterface::QUIET);
$io->writeError('<error>Check https://getcomposer.org/doc/articles/troubleshooting.md#proc-open-fork-failed-errors for details</error>', true, IOInterface::QUIET);
}
}







public function getComposer($required = true, $disablePlugins = false)
{
if (null === $this->composer) {
try {
$this->composer = Factory::create($this->io, null, $disablePlugins);
} catch (\InvalidArgumentException $e) {
if ($required) {
$this->io->writeError($e->getMessage());
exit(1);
}
} catch (JsonValidationException $e) {
$errors = ' - ' . implode(PHP_EOL . ' - ', $e->getErrors());
$message = $e->getMessage() . ':' . PHP_EOL . $errors;
throw new JsonValidationException($message);
}
}

return $this->composer;
}




public function resetComposer()
{
$this->composer = null;
}




public function getIO()
{
return $this->io;
}

public function getHelp()
{
return self::$logo . parent::getHelp();
}




protected function getDefaultCommands()
{
$commands = parent::getDefaultCommands();
$commands[] = new Command\AboutCommand();
$commands[] = new Command\ConfigCommand();
$commands[] = new Command\DependsCommand();
$commands[] = new Command\InitCommand();
$commands[] = new Command\InstallCommand();
$commands[] = new Command\CreateProjectCommand();
$commands[] = new Command\UpdateCommand();
$commands[] = new Command\SearchCommand();
$commands[] = new Command\ValidateCommand();
$commands[] = new Command\ShowCommand();
$commands[] = new Command\SuggestsCommand();
$commands[] = new Command\RequireCommand();
$commands[] = new Command\DumpAutoloadCommand();
$commands[] = new Command\StatusCommand();
$commands[] = new Command\ArchiveCommand();
$commands[] = new Command\DiagnoseCommand();
$commands[] = new Command\RunScriptCommand();
$commands[] = new Command\LicensesCommand();
$commands[] = new Command\GlobalCommand();
$commands[] = new Command\ClearCacheCommand();
$commands[] = new Command\RemoveCommand();
$commands[] = new Command\HomeCommand();

if ('phar:' === substr(__FILE__, 0, 5)) {
$commands[] = new Command\SelfUpdateCommand();
}

return $commands;
}




public function getLongVersion()
{
if (Composer::BRANCH_ALIAS_VERSION) {
return sprintf(
'<info>%s</info> version <comment>%s (%s)</comment> %s',
$this->getName(),
Composer::BRANCH_ALIAS_VERSION,
$this->getVersion(),
Composer::RELEASE_DATE
);
}

return parent::getLongVersion() . ' ' . Composer::RELEASE_DATE;
}




protected function getDefaultInputDefinition()
{
$definition = parent::getDefaultInputDefinition();
$definition->addOption(new InputOption('--profile', null, InputOption::VALUE_NONE, 'Display timing and memory usage information'));
$definition->addOption(new InputOption('--working-dir', '-d', InputOption::VALUE_REQUIRED, 'If specified, use the given directory as working directory.'));

return $definition;
}
}
