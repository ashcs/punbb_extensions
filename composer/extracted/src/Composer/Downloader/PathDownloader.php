<?php











namespace Composer\Downloader;

use Composer\Package\PackageInterface;
use Composer\Util\Platform;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;







class PathDownloader extends FileDownloader
{



public function download(PackageInterface $package, $path)
{
$url = $package->getDistUrl();
$realUrl = realpath($url);
if (false === $realUrl || !file_exists($realUrl) || !is_dir($realUrl)) {
throw new \RuntimeException(sprintf(
'Source path "%s" is not found for package %s', $url, $package->getName()
));
}

if (strpos(realpath($path) . DIRECTORY_SEPARATOR, $realUrl . DIRECTORY_SEPARATOR) === 0) {
throw new \RuntimeException(sprintf(
'Package %s cannot install to "%s" inside its source at "%s"',
$package->getName(), realpath($path), $realUrl
));
}

$fileSystem = new Filesystem();
$this->filesystem->removeDirectory($path);

$this->io->writeError(sprintf(
'  - Installing <info>%s</info> (<comment>%s</comment>)',
$package->getName(),
$package->getFullPrettyVersion()
));

try {
if (Platform::isWindows()) {

 $this->filesystem->junction($realUrl, $path);
$this->io->writeError(sprintf('    Junctioned from %s', $url));

} else {
$shortestPath = $this->filesystem->findShortestPath($path, $realUrl);
$fileSystem->symlink($shortestPath, $path);
$this->io->writeError(sprintf('    Symlinked from %s', $url));
}
} catch (IOException $e) {
$fileSystem->mirror($realUrl, $path);
$this->io->writeError(sprintf('    Mirrored from %s', $url));
}

$this->io->writeError('');
}
}
