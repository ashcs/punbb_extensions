<?php











namespace Composer\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Downloader\ChangeReportInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Script\ScriptEvents;





class StatusCommand extends Command
{
protected function configure()
{
$this
->setName('status')
->setDescription('Show a list of locally modified packages')
->setDefinition(array(
new InputOption('verbose', 'v|vv|vvv', InputOption::VALUE_NONE, 'Show modified files for each directory that contains changes.'),
))
->setHelp(<<<EOT
The status command displays a list of dependencies that have
been modified locally.

EOT
)
;
}

protected function execute(InputInterface $input, OutputInterface $output)
{

 $composer = $this->getComposer();

$commandEvent = new CommandEvent(PluginEvents::COMMAND, 'status', $input, $output);
$composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);

$installedRepo = $composer->getRepositoryManager()->getLocalRepository();

$dm = $composer->getDownloadManager();
$im = $composer->getInstallationManager();


 $composer->getEventDispatcher()->dispatchScript(ScriptEvents::PRE_STATUS_CMD, true);

$errors = array();
$io = $this->getIO();


 foreach ($installedRepo->getPackages() as $package) {
$downloader = $dm->getDownloaderForInstalledPackage($package);

if ($downloader instanceof ChangeReportInterface) {
$targetDir = $im->getInstallPath($package);

if (is_link($targetDir)) {
$errors[$targetDir] = $targetDir . ' is a symbolic link.';
}

if ($changes = $downloader->getLocalChanges($package, $targetDir)) {
$errors[$targetDir] = $changes;
}
}
}


 if (!$errors) {
$io->writeError('<info>No local changes</info>');
} else {
$io->writeError('<error>You have changes in the following dependencies:</error>');
}

foreach ($errors as $path => $changes) {
if ($input->getOption('verbose')) {
$indentedChanges = implode("\n", array_map(function ($line) {
return '    ' . ltrim($line);
}, explode("\n", $changes)));
$io->write('<info>'.$path.'</info>:');
$io->write($indentedChanges);
} else {
$io->write($path);
}
}

if ($errors && !$input->getOption('verbose')) {
$io->writeError('Use --verbose (-v) to see modified files');
}


 $composer->getEventDispatcher()->dispatchScript(ScriptEvents::POST_STATUS_CMD, true);

return $errors ? 1 : 0;
}
}
