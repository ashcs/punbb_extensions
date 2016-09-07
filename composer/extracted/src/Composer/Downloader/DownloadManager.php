<?php











namespace Composer\Downloader;

use Composer\Package\PackageInterface;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;






class DownloadManager
{
private $io;
private $preferDist = false;
private $preferSource = false;
private $filesystem;
private $downloaders = array();








public function __construct(IOInterface $io, $preferSource = false, Filesystem $filesystem = null)
{
$this->io = $io;
$this->preferSource = $preferSource;
$this->filesystem = $filesystem ?: new Filesystem();
}







public function setPreferSource($preferSource)
{
$this->preferSource = $preferSource;

return $this;
}







public function setPreferDist($preferDist)
{
$this->preferDist = $preferDist;

return $this;
}








public function setOutputProgress($outputProgress)
{
foreach ($this->downloaders as $downloader) {
$downloader->setOutputProgress($outputProgress);
}

return $this;
}








public function setDownloader($type, DownloaderInterface $downloader)
{
$type = strtolower($type);
$this->downloaders[$type] = $downloader;

return $this;
}








public function getDownloader($type)
{
$type = strtolower($type);
if (!isset($this->downloaders[$type])) {
throw new \InvalidArgumentException(sprintf('Unknown downloader type: %s. Available types: %s.', $type, implode(', ', array_keys($this->downloaders))));
}

return $this->downloaders[$type];
}










public function getDownloaderForInstalledPackage(PackageInterface $package)
{
$installationSource = $package->getInstallationSource();

if ('metapackage' === $package->getType()) {
return;
}

if ('dist' === $installationSource) {
$downloader = $this->getDownloader($package->getDistType());
} elseif ('source' === $installationSource) {
$downloader = $this->getDownloader($package->getSourceType());
} else {
throw new \InvalidArgumentException(
'Package '.$package.' seems not been installed properly'
);
}

if ($installationSource !== $downloader->getInstallationSource()) {
throw new \LogicException(sprintf(
'Downloader "%s" is a %s type downloader and can not be used to download %s',
get_class($downloader), $downloader->getInstallationSource(), $installationSource
));
}

return $downloader;
}











public function download(PackageInterface $package, $targetDir, $preferSource = null)
{
$preferSource = null !== $preferSource ? $preferSource : $this->preferSource;
$sourceType = $package->getSourceType();
$distType = $package->getDistType();

$sources = array();
if ($sourceType) {
$sources[] = 'source';
}
if ($distType) {
$sources[] = 'dist';
}

if (empty($sources)) {
throw new \InvalidArgumentException('Package '.$package.' must have a source or dist specified');
}

if ((!$package->isDev() || $this->preferDist) && !$preferSource) {
$sources = array_reverse($sources);
}

$this->filesystem->ensureDirectoryExists($targetDir);

foreach ($sources as $i => $source) {
if (isset($e)) {
$this->io->writeError('    <warning>Now trying to download from ' . $source . '</warning>');
}
$package->setInstallationSource($source);
try {
$downloader = $this->getDownloaderForInstalledPackage($package);
if ($downloader) {
$downloader->download($package, $targetDir);
}
break;
} catch (\RuntimeException $e) {
if ($i === count($sources) - 1) {
throw $e;
}

$this->io->writeError(
'    <warning>Failed to download '.
$package->getPrettyName().
' from ' . $source . ': '.
$e->getMessage().'</warning>'
);
}
}
}










public function update(PackageInterface $initial, PackageInterface $target, $targetDir)
{
$downloader = $this->getDownloaderForInstalledPackage($initial);
if (!$downloader) {
return;
}

$installationSource = $initial->getInstallationSource();

if ('dist' === $installationSource) {
$initialType = $initial->getDistType();
$targetType = $target->getDistType();
} else {
$initialType = $initial->getSourceType();
$targetType = $target->getSourceType();
}


 if ($target->isDev() && 'dist' === $installationSource) {
$downloader->remove($initial, $targetDir);
$this->download($target, $targetDir);

return;
}

if ($initialType === $targetType) {
$target->setInstallationSource($installationSource);
try {
$downloader->update($initial, $target, $targetDir);

return;
} catch (\RuntimeException $e) {
if (!$this->io->isInteractive()) {
throw $e;
}
$this->io->writeError('<error>    Update failed ('.$e->getMessage().')</error>');
if (!$this->io->askConfirmation('    Would you like to try reinstalling the package instead [<comment>yes</comment>]? ', true)) {
throw $e;
}
}
}

$downloader->remove($initial, $targetDir);
$this->download($target, $targetDir, 'source' === $installationSource);
}







public function remove(PackageInterface $package, $targetDir)
{
$downloader = $this->getDownloaderForInstalledPackage($package);
if ($downloader) {
$downloader->remove($package, $targetDir);
}
}
}
