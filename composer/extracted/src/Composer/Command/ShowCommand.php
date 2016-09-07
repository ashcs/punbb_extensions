<?php











namespace Composer\Command;

use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\Factory;
use Composer\Package\CompletePackageInterface;
use Composer\Semver\VersionParser;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Package\PackageInterface;
use Composer\Util\Platform;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Repository\ArrayRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\ComposerRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Spdx\SpdxLicenses;






class ShowCommand extends Command
{
protected $versionParser;
protected $colors;

protected function configure()
{
$this
->setName('show')
->setAliases(array('info'))
->setDescription('Show information about packages')
->setDefinition(array(
new InputArgument('package', InputArgument::OPTIONAL, 'Package to inspect'),
new InputArgument('version', InputArgument::OPTIONAL, 'Version or version constraint to inspect'),
new InputOption('installed', 'i', InputOption::VALUE_NONE, 'List installed packages only'),
new InputOption('platform', 'p', InputOption::VALUE_NONE, 'List platform packages only'),
new InputOption('available', 'a', InputOption::VALUE_NONE, 'List available packages only'),
new InputOption('self', 's', InputOption::VALUE_NONE, 'Show the root package information'),
new InputOption('name-only', 'N', InputOption::VALUE_NONE, 'List package names only'),
new InputOption('path', 'P', InputOption::VALUE_NONE, 'Show package paths'),
new InputOption('tree', 't', InputOption::VALUE_NONE, 'List the dependencies as a tree'),
))
->setHelp(<<<EOT
The show command displays detailed information about a package, or
lists all packages available.

EOT
)
;
}

protected function execute(InputInterface $input, OutputInterface $output)
{
$this->versionParser = new VersionParser;
if ($input->getOption('tree')) {
$this->initStyles($output);
}

$composer = $this->getComposer(false);
$io = $this->getIO();

if ($input->getOption('tree') && !$input->getOption('installed')) {
$io->writeError('The --tree (-t) option is only usable in combination with --installed (-i) or by passing a single package name to show, assuming -i');
$input->setOption('installed', true);
}


 $platformOverrides = array();
if ($composer) {
$platformOverrides = $composer->getConfig()->get('platform') ?: array();
}
$platformRepo = new PlatformRepository(array(), $platformOverrides);

if ($input->getOption('self')) {
$package = $this->getComposer()->getPackage();
$repos = $installedRepo = new ArrayRepository(array($package));
} elseif ($input->getOption('platform')) {
$repos = $installedRepo = $platformRepo;
} elseif ($input->getOption('installed')) {
$repos = $installedRepo = $this->getComposer()->getRepositoryManager()->getLocalRepository();
} elseif ($input->getOption('available')) {
$installedRepo = $platformRepo;
if ($composer) {
$repos = new CompositeRepository($composer->getRepositoryManager()->getRepositories());
} else {
$defaultRepos = Factory::createDefaultRepositories($io);
$repos = new CompositeRepository($defaultRepos);
$io->writeError('No composer.json found in the current directory, showing available packages from ' . implode(', ', array_keys($defaultRepos)));
}
} elseif ($composer) {
$localRepo = $composer->getRepositoryManager()->getLocalRepository();
$installedRepo = new CompositeRepository(array($localRepo, $platformRepo));
$repos = new CompositeRepository(array_merge(array($installedRepo), $composer->getRepositoryManager()->getRepositories()));
} else {
$defaultRepos = Factory::createDefaultRepositories($io);
$io->writeError('No composer.json found in the current directory, showing available packages from ' . implode(', ', array_keys($defaultRepos)));
$installedRepo = $platformRepo;
$repos = new CompositeRepository(array_merge(array($installedRepo), $defaultRepos));
}

if ($composer) {
$commandEvent = new CommandEvent(PluginEvents::COMMAND, 'show', $input, $output);
$composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);
}


 if ($input->getArgument('package') || !empty($package)) {
$versions = array();
if (empty($package)) {
list($package, $versions) = $this->getPackage($installedRepo, $repos, $input->getArgument('package'), $input->getArgument('version'));

if (!$package) {
throw new \InvalidArgumentException('Package '.$input->getArgument('package').' not found');
}
} else {
$versions = array($package->getPrettyVersion() => $package->getVersion());
}

if ($input->getOption('tree')) {
$this->displayPackageTree($package, $installedRepo, $repos, $output);
} else {
$this->printMeta($package, $versions, $installedRepo, $repos);
$this->printLinks($package, 'requires');
$this->printLinks($package, 'devRequires', 'requires (dev)');
if ($package->getSuggests()) {
$io->write("\n<info>suggests</info>");
foreach ($package->getSuggests() as $suggested => $reason) {
$io->write($suggested . ' <comment>' . $reason . '</comment>');
}
}
$this->printLinks($package, 'provides');
$this->printLinks($package, 'conflicts');
$this->printLinks($package, 'replaces');
}

return;
}


 if ($input->getOption('tree')) {
$rootPackage = $this->getComposer()->getPackage();
$rootRequires = array_map(
'strtolower',
array_keys(array_merge($rootPackage->getRequires(), $rootPackage->getDevRequires()))
);

foreach ($installedRepo->getPackages() as $package) {
if (in_array($package->getName(), $rootRequires, true)) {
$this->displayPackageTree($package, $installedRepo, $repos, $output);
}
}

return 0;
}

if ($repos instanceof CompositeRepository) {
$repos = $repos->getRepositories();
} elseif (!is_array($repos)) {
$repos = array($repos);
}


 $packages = array();

foreach ($repos as $repo) {
if ($repo === $platformRepo) {
$type = '<info>platform</info>:';
} elseif (
$repo === $installedRepo
|| ($installedRepo instanceof CompositeRepository && in_array($repo, $installedRepo->getRepositories(), true))
) {
$type = '<info>installed</info>:';
} else {
$type = '<comment>available</comment>:';
}
if ($repo instanceof ComposerRepository && $repo->hasProviders()) {
foreach ($repo->getProviderNames() as $name) {
$packages[$type][$name] = $name;
}
} else {
foreach ($repo->getPackages() as $package) {
if (!isset($packages[$type][$package->getName()])
|| !is_object($packages[$type][$package->getName()])
|| version_compare($packages[$type][$package->getName()]->getVersion(), $package->getVersion(), '<')
) {
$packages[$type][$package->getName()] = $package;
}
}
}
}

$showAllTypes = !$input->getOption('platform') && !$input->getOption('installed') && !$input->getOption('available');
$indent = $showAllTypes ? '  ' : '';
foreach (array('<info>platform</info>:' => true, '<comment>available</comment>:' => false, '<info>installed</info>:' => true) as $type => $showVersion) {
if (isset($packages[$type])) {
if ($showAllTypes) {
$io->write($type);
}
ksort($packages[$type]);

$nameLength = $versionLength = 0;
foreach ($packages[$type] as $package) {
if (is_object($package)) {
$nameLength = max($nameLength, strlen($package->getPrettyName()));
$versionLength = max($versionLength, strlen($package->getFullPrettyVersion()));
} else {
$nameLength = max($nameLength, $package);
}
}
list($width) = $this->getApplication()->getTerminalDimensions();
if (null === $width) {

 
 $width = PHP_INT_MAX;
}
if (Platform::isWindows()) {
$width--;
}

if ($input->getOption('path') && null === $composer) {
$io->writeError('No composer.json found in the current directory, disabling "path" option');
$input->setOption('path', false);
}

$writePath = !$input->getOption('name-only') && $input->getOption('path');
$writeVersion = !$input->getOption('name-only') && !$input->getOption('path') && $showVersion && ($nameLength + $versionLength + 3 <= $width);
$writeDescription = !$input->getOption('name-only') && !$input->getOption('path') && ($nameLength + ($showVersion ? $versionLength : 0) + 24 <= $width);
foreach ($packages[$type] as $package) {
if (is_object($package)) {
$io->write($indent . str_pad($package->getPrettyName(), $nameLength, ' '), false);

if ($writeVersion) {
$io->write(' ' . str_pad($package->getFullPrettyVersion(), $versionLength, ' '), false);
}

if ($writeDescription) {
$description = strtok($package->getDescription(), "\r\n");
$remaining = $width - $nameLength - $versionLength - 4;
if (strlen($description) > $remaining) {
$description = substr($description, 0, $remaining - 3) . '...';
}
$io->write(' ' . $description, false);
}

if ($writePath) {
$path = strtok(realpath($composer->getInstallationManager()->getInstallPath($package)), "\r\n");
$io->write(' ' . $path, false);
}
} else {
$io->write($indent . $package, false);
}
$io->write('');
}
if ($showAllTypes) {
$io->write('');
}
}
}
}











protected function getPackage(RepositoryInterface $installedRepo, RepositoryInterface $repos, $name, $version = null)
{
$name = strtolower($name);
$constraint = null;
if ($version) {
$constraint = $this->versionParser->parseConstraints($version);
}

$policy = new DefaultPolicy();
$pool = new Pool('dev');
$pool->addRepository($repos);

$matchedPackage = null;
$versions = array();
$matches = $pool->whatProvides($name, $constraint);
foreach ($matches as $index => $package) {

 if ($package->getName() !== $name) {
unset($matches[$index]);
continue;
}


 if (null === $version && $installedRepo->hasPackage($package)) {
$matchedPackage = $package;
}

$versions[$package->getPrettyVersion()] = $package->getVersion();
$matches[$index] = $package->getId();
}


 if (!$matchedPackage && $matches && $preferred = $policy->selectPreferredPackages($pool, array(), $matches)) {
$matchedPackage = $pool->literalToPackage($preferred[0]);
}

return array($matchedPackage, $versions);
}




protected function printMeta(CompletePackageInterface $package, array $versions, RepositoryInterface $installedRepo)
{
$io = $this->getIO();
$io->write('<info>name</info>     : ' . $package->getPrettyName());
$io->write('<info>descrip.</info> : ' . $package->getDescription());
$io->write('<info>keywords</info> : ' . join(', ', $package->getKeywords() ?: array()));
$this->printVersions($package, $versions, $installedRepo);
$io->write('<info>type</info>     : ' . $package->getType());
$this->printLicenses($package);
$io->write('<info>source</info>   : ' . sprintf('[%s] <comment>%s</comment> %s', $package->getSourceType(), $package->getSourceUrl(), $package->getSourceReference()));
$io->write('<info>dist</info>     : ' . sprintf('[%s] <comment>%s</comment> %s', $package->getDistType(), $package->getDistUrl(), $package->getDistReference()));
$io->write('<info>names</info>    : ' . implode(', ', $package->getNames()));

if ($package->isAbandoned()) {
$replacement = ($package->getReplacementPackage() !== null)
? ' The author suggests using the ' . $package->getReplacementPackage(). ' package instead.'
: null;

$io->writeError(
sprintf('<warning>Attention: This package is abandoned and no longer maintained.%s</warning>', $replacement)
);
}

if ($package->getSupport()) {
$io->write("\n<info>support</info>");
foreach ($package->getSupport() as $type => $value) {
$io->write('<comment>' . $type . '</comment> : '.$value);
}
}

if ($package->getAutoload()) {
$io->write("\n<info>autoload</info>");
foreach ($package->getAutoload() as $type => $autoloads) {
$io->write('<comment>' . $type . '</comment>');

if ($type === 'psr-0') {
foreach ($autoloads as $name => $path) {
$io->write(($name ?: '*') . ' => ' . (is_array($path) ? implode(', ', $path) : ($path ?: '.')));
}
} elseif ($type === 'psr-4') {
foreach ($autoloads as $name => $path) {
$io->write(($name ?: '*') . ' => ' . (is_array($path) ? implode(', ', $path) : ($path ?: '.')));
}
} elseif ($type === 'classmap') {
$io->write(implode(', ', $autoloads));
}
}
if ($package->getIncludePaths()) {
$io->write('<comment>include-path</comment>');
$io->write(implode(', ', $package->getIncludePaths()));
}
}
}




protected function printVersions(CompletePackageInterface $package, array $versions, RepositoryInterface $installedRepo)
{
uasort($versions, 'version_compare');
$versions = array_keys(array_reverse($versions));


 if ($installedRepo->hasPackage($package)) {
$installedVersion = $package->getPrettyVersion();
$key = array_search($installedVersion, $versions);
if (false !== $key) {
$versions[$key] = '<info>* ' . $installedVersion . '</info>';
}
}

$versions = implode(', ', $versions);

$this->getIO()->write('<info>versions</info> : ' . $versions);
}








protected function printLinks(CompletePackageInterface $package, $linkType, $title = null)
{
$title = $title ?: $linkType;
$io = $this->getIO();
if ($links = $package->{'get'.ucfirst($linkType)}()) {
$io->write("\n<info>" . $title . "</info>");

foreach ($links as $link) {
$io->write($link->getTarget() . ' <comment>' . $link->getPrettyConstraint() . '</comment>');
}
}
}






protected function printLicenses(CompletePackageInterface $package)
{
$spdxLicenses = new SpdxLicenses();

$licenses = $package->getLicense();
$io = $this->getIO();

foreach ($licenses as $licenseId) {
$license = $spdxLicenses->getLicenseByIdentifier($licenseId); 

if (!$license) {
$out = $licenseId;
} else {

 if ($license[1] === true) {
$out = sprintf('%s (%s) (OSI approved) %s', $license[0], $licenseId, $license[2]);
} else {
$out = sprintf('%s (%s) %s', $license[0], $licenseId, $license[2]);
}
}

$io->write('<info>license</info>  : ' . $out);
}
}






protected function initStyles(OutputInterface $output)
{
$this->colors = array(
'green',
'yellow',
'cyan',
'magenta',
'blue',
);

foreach ($this->colors as $color) {
$style = new OutputFormatterStyle($color);
$output->getFormatter()->setStyle($color, $style);
}
}









protected function displayPackageTree(PackageInterface $package, RepositoryInterface $installedRepo, RepositoryInterface $distantRepos, OutputInterface $output)
{
$packagesInTree = array();
$packagesInTree[] = $package;

$io = $this->getIO();
$io->write(sprintf('<info>%s</info>', $package->getPrettyName()), false);
$io->write(' ' . $package->getPrettyVersion(), false);
$io->write(' ' . strtok($package->getDescription(), "\r\n"));

if (is_object($package)) {
$requires = $package->getRequires();
$treeBar = '├';
$j = 0;
$total = count($requires);
foreach ($requires as $requireName => $require) {
$j++;
if ($j == 0) {
$this->writeTreeLine($treeBar);
}
if ($j == $total) {
$treeBar = '└';
}
$level = 1;
$color = $this->colors[$level];
$info = sprintf('%s──<%s>%s</%s> %s', $treeBar, $color, $requireName, $color, $require->getPrettyConstraint());
$this->writeTreeLine($info);

$treeBar = str_replace('└', ' ', $treeBar);

$packagesInTree[] = $requireName;

$this->displayTree($requireName, $require, $installedRepo, $distantRepos, $packagesInTree, $output, $treeBar, $level + 1);
}
}
}













protected function displayTree($name, $package, RepositoryInterface $installedRepo, RepositoryInterface $distantRepos, array $packagesInTree, OutputInterface $output, $previousTreeBar = '├', $level = 1)
{
$previousTreeBar = str_replace('├', '│', $previousTreeBar);
list($package, $versions) = $this->getPackage($installedRepo, $distantRepos, $name, $package->getPrettyConstraint() === 'self.version' ? $package->getConstraint() : $package->getPrettyConstraint());
if (is_object($package)) {
$requires = $package->getRequires();
$treeBar = $previousTreeBar . '  ├';
$i = 0;
$total = count($requires);
foreach ($requires as $requireName => $require) {
$i++;
if ($i == $total) {
$treeBar = $previousTreeBar . '  └';
}
$colorIdent = $level % count($this->colors);
$color = $this->colors[$colorIdent];
$info = sprintf('%s──<%s>%s</%s> %s', $treeBar, $color, $requireName, $color, $require->getPrettyConstraint());
$this->writeTreeLine($info);

$treeBar = str_replace('└', ' ', $treeBar);
if (!in_array($requireName, $packagesInTree)) {
$packagesInTree[] = $requireName;
$this->displayTree($requireName, $require, $installedRepo, $distantRepos, $packagesInTree, $output, $treeBar, $level + 1);
}
}
}
}

private function writeTreeLine($line)
{
$io = $this->getIO();
if (!$io->isDecorated()) {
$line = str_replace(array('└', '├', '──', '│'), array('`-', '|-', '-', '|'), $line);
}

$io->write($line);
}
}
