<?php











namespace Composer\Downloader;

use Composer\Config;
use Composer\Cache;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PreFileDownloadEvent;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;









class FileDownloader implements DownloaderInterface
{
protected $io;
protected $config;
protected $rfs;
protected $filesystem;
protected $cache;
protected $outputProgress = true;
private $lastCacheWrites = array();











public function __construct(IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null, Cache $cache = null, RemoteFilesystem $rfs = null, Filesystem $filesystem = null)
{
$this->io = $io;
$this->config = $config;
$this->eventDispatcher = $eventDispatcher;
$this->rfs = $rfs ?: Factory::createRemoteFilesystem($this->io, $config);
$this->filesystem = $filesystem ?: new Filesystem();
$this->cache = $cache;

if ($this->cache && $this->cache->gcIsNecessary()) {
$this->cache->gc($config->get('cache-files-ttl'), $config->get('cache-files-maxsize'));
}
}




public function getInstallationSource()
{
return 'dist';
}




public function download(PackageInterface $package, $path)
{
if (!$package->getDistUrl()) {
throw new \InvalidArgumentException('The given package is missing url information');
}

$this->io->writeError("  - Installing <info>" . $package->getName() . "</info> (<comment>" . $package->getFullPrettyVersion() . "</comment>)");

$urls = $package->getDistUrls();
while ($url = array_shift($urls)) {
try {
return $this->doDownload($package, $path, $url);
} catch (\Exception $e) {
if ($this->io->isDebug()) {
$this->io->writeError('');
$this->io->writeError('Failed: ['.get_class($e).'] '.$e->getCode().': '.$e->getMessage());
} elseif (count($urls)) {
$this->io->writeError('');
$this->io->writeError('    Failed, trying the next URL ('.$e->getCode().': '.$e->getMessage().')');
}

if (!count($urls)) {
throw $e;
}
}
}

$this->io->writeError('');
}

protected function doDownload(PackageInterface $package, $path, $url)
{
$this->filesystem->emptyDirectory($path);

$fileName = $this->getFileName($package, $path);

$processedUrl = $this->processUrl($package, $url);
$hostname = parse_url($processedUrl, PHP_URL_HOST);

$preFileDownloadEvent = new PreFileDownloadEvent(PluginEvents::PRE_FILE_DOWNLOAD, $this->rfs, $processedUrl);
if ($this->eventDispatcher) {
$this->eventDispatcher->dispatch($preFileDownloadEvent->getName(), $preFileDownloadEvent);
}
$rfs = $preFileDownloadEvent->getRemoteFilesystem();

try {
$checksum = $package->getDistSha1Checksum();
$cacheKey = $this->getCacheKey($package, $processedUrl);


 if (!$this->cache || ($checksum && $checksum !== $this->cache->sha1($cacheKey)) || !$this->cache->copyTo($cacheKey, $fileName)) {
if (!$this->outputProgress) {
$this->io->writeError('    Downloading');
}


 $retries = 3;
while ($retries--) {
try {
$rfs->copy($hostname, $processedUrl, $fileName, $this->outputProgress, $package->getTransportOptions());
break;
} catch (TransportException $e) {

 if ((0 !== $e->getCode() && !in_array($e->getCode(), array(500, 502, 503, 504))) || !$retries) {
throw $e;
}
$this->io->writeError('    Download failed, retrying...', true, IOInterface::VERBOSE);
usleep(500000);
}
}

if ($this->cache) {
$this->lastCacheWrites[$package->getName()] = $cacheKey;
$this->cache->copyFrom($cacheKey, $fileName);
}
} else {
$this->io->writeError('    Loading from cache');
}

if (!file_exists($fileName)) {
throw new \UnexpectedValueException($url.' could not be saved to '.$fileName.', make sure the'
.' directory is writable and you have internet connectivity');
}

if ($checksum && hash_file('sha1', $fileName) !== $checksum) {
throw new \UnexpectedValueException('The checksum verification of the file failed (downloaded from '.$url.')');
}
} catch (\Exception $e) {

 $this->filesystem->removeDirectory($path);
$this->clearLastCacheWrite($package);
throw $e;
}

return $fileName;
}




public function setOutputProgress($outputProgress)
{
$this->outputProgress = $outputProgress;

return $this;
}

protected function clearLastCacheWrite(PackageInterface $package)
{
if ($this->cache && isset($this->lastCacheWrites[$package->getName()])) {
$this->cache->remove($this->lastCacheWrites[$package->getName()]);
unset($this->lastCacheWrites[$package->getName()]);
}
}




public function update(PackageInterface $initial, PackageInterface $target, $path)
{
$this->remove($initial, $path);
$this->download($target, $path);
}




public function remove(PackageInterface $package, $path)
{
$this->io->writeError("  - Removing <info>" . $package->getName() . "</info> (<comment>" . $package->getFullPrettyVersion() . "</comment>)");
if (!$this->filesystem->removeDirectory($path)) {
throw new \RuntimeException('Could not completely delete '.$path.', aborting.');
}
}








protected function getFileName(PackageInterface $package, $path)
{
return $path.'/'.pathinfo(parse_url($package->getDistUrl(), PHP_URL_PATH), PATHINFO_BASENAME);
}









protected function processUrl(PackageInterface $package, $url)
{
if (!extension_loaded('openssl') && 0 === strpos($url, 'https:')) {
throw new \RuntimeException('You must enable the openssl extension to download files via https');
}

return $url;
}

private function getCacheKey(PackageInterface $package, $processedUrl)
{

 
 
 
 $cacheKey = sha1($processedUrl);

return $package->getName().'/'.$cacheKey.'.'.$package->getDistType();
}
}
