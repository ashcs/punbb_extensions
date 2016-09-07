<?php











namespace Composer\Installer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Composer\Util\Platform;
use Composer\Util\ProcessExecutor;
use Composer\Util\Silencer;







class LibraryInstaller implements InstallerInterface
{
protected $composer;
protected $vendorDir;
protected $binDir;
protected $downloadManager;
protected $io;
protected $type;
protected $filesystem;
protected $binCompat;









public function __construct(IOInterface $io, Composer $composer, $type = 'library', Filesystem $filesystem = null)
{
$this->composer = $composer;
$this->downloadManager = $composer->getDownloadManager();
$this->io = $io;
$this->type = $type;

$this->filesystem = $filesystem ?: new Filesystem();
$this->vendorDir = rtrim($composer->getConfig()->get('vendor-dir'), '/');
$this->binDir = rtrim($composer->getConfig()->get('bin-dir'), '/');
$this->binCompat = $composer->getConfig()->get('bin-compat');
}




public function supports($packageType)
{
return $packageType === $this->type || null === $this->type;
}




public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
{
return $repo->hasPackage($package) && is_readable($this->getInstallPath($package));
}




public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
{
$this->initializeVendorDir();
$downloadPath = $this->getInstallPath($package);


 if (!is_readable($downloadPath) && $repo->hasPackage($package)) {
$this->removeBinaries($package);
}

$this->installCode($package);
$this->installBinaries($package);
if (!$repo->hasPackage($package)) {
$repo->addPackage(clone $package);
}
}




public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
{
if (!$repo->hasPackage($initial)) {
throw new \InvalidArgumentException('Package is not installed: '.$initial);
}

$this->initializeVendorDir();

$this->removeBinaries($initial);
$this->updateCode($initial, $target);
$this->installBinaries($target);
$repo->removePackage($initial);
if (!$repo->hasPackage($target)) {
$repo->addPackage(clone $target);
}
}




public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
{
if (!$repo->hasPackage($package)) {
throw new \InvalidArgumentException('Package is not installed: '.$package);
}

$this->removeCode($package);
$this->removeBinaries($package);
$repo->removePackage($package);

$downloadPath = $this->getPackageBasePath($package);
if (strpos($package->getName(), '/')) {
$packageVendorDir = dirname($downloadPath);
if (is_dir($packageVendorDir) && $this->filesystem->isDirEmpty($packageVendorDir)) {
Silencer::call('rmdir', $packageVendorDir);
}
}
}




public function getInstallPath(PackageInterface $package)
{
$this->initializeVendorDir();

$basePath = ($this->vendorDir ? $this->vendorDir.'/' : '') . $package->getPrettyName();
$targetDir = $package->getTargetDir();

return $basePath . ($targetDir ? '/'.$targetDir : '');
}










protected function getPackageBasePath(PackageInterface $package)
{
$installPath = $this->getInstallPath($package);
$targetDir = $package->getTargetDir();

if ($targetDir) {
return preg_replace('{/*'.str_replace('/', '/+', preg_quote($targetDir)).'/?$}', '', $installPath);
}

return $installPath;
}

protected function installCode(PackageInterface $package)
{
$downloadPath = $this->getInstallPath($package);
$this->downloadManager->download($package, $downloadPath);
}

protected function updateCode(PackageInterface $initial, PackageInterface $target)
{
$initialDownloadPath = $this->getInstallPath($initial);
$targetDownloadPath = $this->getInstallPath($target);
if ($targetDownloadPath !== $initialDownloadPath) {

 
 if (substr($initialDownloadPath, 0, strlen($targetDownloadPath)) === $targetDownloadPath
|| substr($targetDownloadPath, 0, strlen($initialDownloadPath)) === $initialDownloadPath
) {
$this->removeCode($initial);
$this->installCode($target);

return;
}

$this->filesystem->rename($initialDownloadPath, $targetDownloadPath);
}
$this->downloadManager->update($initial, $target, $targetDownloadPath);
}

protected function removeCode(PackageInterface $package)
{
$downloadPath = $this->getPackageBasePath($package);
$this->downloadManager->remove($package, $downloadPath);
}

protected function getBinaries(PackageInterface $package)
{
return $package->getBinaries();
}

protected function installBinaries(PackageInterface $package)
{
$binaries = $this->getBinaries($package);
if (!$binaries) {
return;
}
foreach ($binaries as $bin) {
$binPath = $this->getInstallPath($package).'/'.$bin;
if (!file_exists($binPath)) {
$this->io->writeError('    <warning>Skipped installation of bin '.$bin.' for package '.$package->getName().': file not found in package</warning>');
continue;
}


 
 
 
 $binPath = realpath($binPath);

$this->initializeBinDir();
$link = $this->binDir.'/'.basename($bin);
if (file_exists($link)) {
if (is_link($link)) {

 
 
 Silencer::call('chmod', $link, 0777 & ~umask());
}
$this->io->writeError('    Skipped installation of bin '.$bin.' for package '.$package->getName().': name conflicts with an existing file');
continue;
}

if ($this->binCompat === "auto") {
if (Platform::isWindows()) {
$this->installFullBinaries($binPath, $link, $bin, $package);
} else {
$this->installSymlinkBinaries($binPath, $link);
}
} elseif ($this->binCompat === "full") {
$this->installFullBinaries($binPath, $link, $bin, $package);
}
Silencer::call('chmod', $link, 0777 & ~umask());
}
}

protected function installFullBinaries($binPath, $link, $bin, PackageInterface $package)
{

 if ('.bat' !== substr($binPath, -4)) {
$this->installUnixyProxyBinaries($binPath, $link);
@chmod($link, 0777 & ~umask());
$link .= '.bat';
if (file_exists($link)) {
$this->io->writeError('    Skipped installation of bin '.$bin.'.bat proxy for package '.$package->getName().': a .bat proxy was already installed');
}
}
if (!file_exists($link)) {
file_put_contents($link, $this->generateWindowsProxyCode($binPath, $link));
}
}

protected function installSymlinkBinaries($binPath, $link)
{
if (!$this->filesystem->relativeSymlink($binPath, $link)) {
$this->installUnixyProxyBinaries($binPath, $link);
}
}

protected function installUnixyProxyBinaries($binPath, $link)
{
file_put_contents($link, $this->generateUnixyProxyCode($binPath, $link));
}

protected function removeBinaries(PackageInterface $package)
{
$binaries = $this->getBinaries($package);
if (!$binaries) {
return;
}
foreach ($binaries as $bin) {
$link = $this->binDir.'/'.basename($bin);
if (is_link($link) || file_exists($link)) {
$this->filesystem->unlink($link);
}
if (file_exists($link.'.bat')) {
$this->filesystem->unlink($link.'.bat');
}
}


 if ((is_dir($this->binDir)) && ($this->filesystem->isDirEmpty($this->binDir))) {
Silencer::call('rmdir', $this->binDir);
}
}

protected function initializeVendorDir()
{
$this->filesystem->ensureDirectoryExists($this->vendorDir);
$this->vendorDir = realpath($this->vendorDir);
}

protected function initializeBinDir()
{
$this->filesystem->ensureDirectoryExists($this->binDir);
$this->binDir = realpath($this->binDir);
}

protected function generateWindowsProxyCode($bin, $link)
{
$binPath = $this->filesystem->findShortestPath($link, $bin);
if ('.bat' === substr($bin, -4) || '.exe' === substr($bin, -4)) {
$caller = 'call';
} else {
$handle = fopen($bin, 'r');
$line = fgets($handle);
fclose($handle);
if (preg_match('{^#!/(?:usr/bin/env )?(?:[^/]+/)*(.+)$}m', $line, $match)) {
$caller = trim($match[1]);
} else {
$caller = 'php';
}
}

return "@ECHO OFF\r\n".
"SET BIN_TARGET=%~dp0/".trim(ProcessExecutor::escape($binPath), '"')."\r\n".
"{$caller} \"%BIN_TARGET%\" %*\r\n";
}

protected function generateUnixyProxyCode($bin, $link)
{
$binPath = $this->filesystem->findShortestPath($link, $bin);

$binDir = ProcessExecutor::escape(dirname($binPath));
$binFile = basename($binPath);

$proxyCode = <<<PROXY
#!/usr/bin/env sh

dir=$(d=\${0%[/\\\\]*}; cd "\$d"; cd $binDir && pwd)

# See if we are running in Cygwin by checking for cygpath program
if command -v 'cygpath' >/dev/null 2>&1; then
	# Cygwin paths start with /cygdrive/ which will break windows PHP,
	# so we need to translate the dir path to windows format. However
	# we could be using cygwin PHP which does not require this, so we
	# test if the path to PHP starts with /cygdrive/ rather than /usr/bin
	if [[ $(which php) == /cygdrive/* ]]; then
		dir=$(cygpath -m \$dir);
	fi
fi

dir=$(echo \$dir | sed 's/ /\ /g')
"\${dir}/$binFile" "$@"

PROXY;

return $proxyCode;
}
}
