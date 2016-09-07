<?php











namespace Composer\Downloader;

use Composer\Config;
use Composer\Cache;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Package\PackageInterface;
use Composer\Util\Platform;
use Composer\Util\ProcessExecutor;
use Composer\Util\RemoteFilesystem;
use Composer\IO\IOInterface;






class GzipDownloader extends ArchiveDownloader
{
protected $process;

public function __construct(IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null, Cache $cache = null, ProcessExecutor $process = null, RemoteFilesystem $rfs = null)
{
$this->process = $process ?: new ProcessExecutor($io);
parent::__construct($io, $config, $eventDispatcher, $cache, $rfs);
}

protected function extract($file, $path)
{
$targetFilepath = $path . DIRECTORY_SEPARATOR . basename(substr($file, 0, -3));


 if (!Platform::isWindows()) {
$command = 'gzip -cd ' . ProcessExecutor::escape($file) . ' > ' . ProcessExecutor::escape($targetFilepath);

if (0 === $this->process->execute($command, $ignoredOutput)) {
return;
}

if (extension_loaded('zlib')) {

 $this->extractUsingExt($file, $targetFilepath);

return;
}

$processError = 'Failed to execute ' . $command . "\n\n" . $this->process->getErrorOutput();
throw new \RuntimeException($processError);
}


 $this->extractUsingExt($file, $targetFilepath);
}




protected function getFileName(PackageInterface $package, $path)
{
return $path.'/'.pathinfo(parse_url($package->getDistUrl(), PHP_URL_PATH), PATHINFO_BASENAME);
}

private function extractUsingExt($file, $targetFilepath)
{
$archiveFile = gzopen($file, 'rb');
$targetFile = fopen($targetFilepath, 'wb');
while ($string = gzread($archiveFile, 4096)) {
fwrite($targetFile, $string, strlen($string));
}
gzclose($archiveFile);
fclose($targetFile);
}
}
