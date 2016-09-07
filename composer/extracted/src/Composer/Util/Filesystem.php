<?php











namespace Composer\Util;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;





class Filesystem
{
private $processExecutor;

public function __construct(ProcessExecutor $executor = null)
{
$this->processExecutor = $executor ?: new ProcessExecutor();
}

public function remove($file)
{
if (is_dir($file)) {
return $this->removeDirectory($file);
}

if (file_exists($file)) {
return $this->unlink($file);
}

return false;
}







public function isDirEmpty($dir)
{
$finder = Finder::create()
->ignoreVCS(false)
->ignoreDotFiles(false)
->depth(0)
->in($dir);

return count($finder) === 0;
}

public function emptyDirectory($dir, $ensureDirectoryExists = true)
{
if (file_exists($dir) && is_link($dir)) {
$this->unlink($dir);
}

if ($ensureDirectoryExists) {
$this->ensureDirectoryExists($dir);
}

if (is_dir($dir)) {
$finder = Finder::create()
->ignoreVCS(false)
->ignoreDotFiles(false)
->depth(0)
->in($dir);

foreach ($finder as $path) {
$this->remove((string) $path);
}
}
}











public function removeDirectory($directory)
{
if ($this->isSymlinkedDirectory($directory)) {
return $this->unlinkSymlinkedDirectory($directory);
}

if ($this->isJunction($directory)) {
return $this->removeJunction($directory);
}

if (!file_exists($directory) || !is_dir($directory)) {
return true;
}

if (preg_match('{^(?:[a-z]:)?[/\\\\]+$}i', $directory)) {
throw new \RuntimeException('Aborting an attempted deletion of '.$directory.', this was probably not intended, if it is a real use case please report it.');
}

if (!function_exists('proc_open')) {
return $this->removeDirectoryPhp($directory);
}

if (Platform::isWindows()) {
$cmd = sprintf('rmdir /S /Q %s', ProcessExecutor::escape(realpath($directory)));
} else {
$cmd = sprintf('rm -rf %s', ProcessExecutor::escape($directory));
}

$result = $this->getProcess()->execute($cmd, $output) === 0;


 clearstatcache();

if ($result && !file_exists($directory)) {
return true;
}

return $this->removeDirectoryPhp($directory);
}











public function removeDirectoryPhp($directory)
{
$it = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
$ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

foreach ($ri as $file) {
if ($file->isDir()) {
$this->rmdir($file->getPathname());
} else {
$this->unlink($file->getPathname());
}
}

return $this->rmdir($directory);
}

public function ensureDirectoryExists($directory)
{
if (!is_dir($directory)) {
if (file_exists($directory)) {
throw new \RuntimeException(
$directory.' exists and is not a directory.'
);
}
if (!@mkdir($directory, 0777, true)) {
throw new \RuntimeException(
$directory.' does not exist and could not be created.'
);
}
}
}








public function unlink($path)
{
if (!@$this->unlinkImplementation($path)) {

 if (!Platform::isWindows() || (usleep(350000) && !@$this->unlinkImplementation($path))) {
$error = error_get_last();
$message = 'Could not delete '.$path.': ' . @$error['message'];
if (Platform::isWindows()) {
$message .= "\nThis can be due to an antivirus or the Windows Search Indexer locking the file while they are analyzed";
}

throw new \RuntimeException($message);
}
}

return true;
}








public function rmdir($path)
{
if (!@rmdir($path)) {

 if (!Platform::isWindows() || (usleep(350000) && !@rmdir($path))) {
$error = error_get_last();
$message = 'Could not delete '.$path.': ' . @$error['message'];
if (Platform::isWindows()) {
$message .= "\nThis can be due to an antivirus or the Windows Search Indexer locking the file while they are analyzed";
}

throw new \RuntimeException($message);
}
}

return true;
}










public function copyThenRemove($source, $target)
{
if (!is_dir($source)) {
copy($source, $target);
$this->unlink($source);

return;
}

$it = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
$ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);
$this->ensureDirectoryExists($target);

foreach ($ri as $file) {
$targetPath = $target . DIRECTORY_SEPARATOR . $ri->getSubPathName();
if ($file->isDir()) {
$this->ensureDirectoryExists($targetPath);
} else {
copy($file->getPathname(), $targetPath);
}
}

$this->removeDirectoryPhp($source);
}

public function rename($source, $target)
{
if (true === @rename($source, $target)) {
return;
}

if (!function_exists('proc_open')) {
return $this->copyThenRemove($source, $target);
}

if (Platform::isWindows()) {

 $command = sprintf('xcopy %s %s /E /I /Q /Y', ProcessExecutor::escape($source), ProcessExecutor::escape($target));
$result = $this->processExecutor->execute($command, $output);


 clearstatcache();

if (0 === $result) {
$this->remove($source);

return;
}
} else {

 
 $command = sprintf('mv %s %s', ProcessExecutor::escape($source), ProcessExecutor::escape($target));
$result = $this->processExecutor->execute($command, $output);


 clearstatcache();

if (0 === $result) {
return;
}
}

return $this->copyThenRemove($source, $target);
}










public function findShortestPath($from, $to, $directories = false)
{
if (!$this->isAbsolutePath($from) || !$this->isAbsolutePath($to)) {
throw new \InvalidArgumentException(sprintf('$from (%s) and $to (%s) must be absolute paths.', $from, $to));
}

$from = lcfirst($this->normalizePath($from));
$to = lcfirst($this->normalizePath($to));

if ($directories) {
$from = rtrim($from, '/') . '/dummy_file';
}

if (dirname($from) === dirname($to)) {
return './'.basename($to);
}

$commonPath = $to;
while (strpos($from.'/', $commonPath.'/') !== 0 && '/' !== $commonPath && !preg_match('{^[a-z]:/?$}i', $commonPath)) {
$commonPath = strtr(dirname($commonPath), '\\', '/');
}

if (0 !== strpos($from, $commonPath) || '/' === $commonPath) {
return $to;
}

$commonPath = rtrim($commonPath, '/') . '/';
$sourcePathDepth = substr_count(substr($from, strlen($commonPath)), '/');
$commonPathCode = str_repeat('../', $sourcePathDepth);

return ($commonPathCode . substr($to, strlen($commonPath))) ?: './';
}










public function findShortestPathCode($from, $to, $directories = false)
{
if (!$this->isAbsolutePath($from) || !$this->isAbsolutePath($to)) {
throw new \InvalidArgumentException(sprintf('$from (%s) and $to (%s) must be absolute paths.', $from, $to));
}

$from = lcfirst($this->normalizePath($from));
$to = lcfirst($this->normalizePath($to));

if ($from === $to) {
return $directories ? '__DIR__' : '__FILE__';
}

$commonPath = $to;
while (strpos($from.'/', $commonPath.'/') !== 0 && '/' !== $commonPath && !preg_match('{^[a-z]:/?$}i', $commonPath) && '.' !== $commonPath) {
$commonPath = strtr(dirname($commonPath), '\\', '/');
}

if (0 !== strpos($from, $commonPath) || '/' === $commonPath || '.' === $commonPath) {
return var_export($to, true);
}

$commonPath = rtrim($commonPath, '/') . '/';
if (strpos($to, $from.'/') === 0) {
return '__DIR__ . '.var_export(substr($to, strlen($from)), true);
}
$sourcePathDepth = substr_count(substr($from, strlen($commonPath)), '/') + $directories;
$commonPathCode = str_repeat('dirname(', $sourcePathDepth).'__DIR__'.str_repeat(')', $sourcePathDepth);
$relTarget = substr($to, strlen($commonPath));

return $commonPathCode . (strlen($relTarget) ? '.' . var_export('/' . $relTarget, true) : '');
}







public function isAbsolutePath($path)
{
return substr($path, 0, 1) === '/' || substr($path, 1, 1) === ':';
}









public function size($path)
{
if (!file_exists($path)) {
throw new \RuntimeException("$path does not exist.");
}
if (is_dir($path)) {
return $this->directorySize($path);
}

return filesize($path);
}








public function normalizePath($path)
{
$parts = array();
$path = strtr($path, '\\', '/');
$prefix = '';
$absolute = false;

if (preg_match('{^([0-9a-z]+:(?://(?:[a-z]:)?)?)}i', $path, $match)) {
$prefix = $match[1];
$path = substr($path, strlen($prefix));
}

if (substr($path, 0, 1) === '/') {
$absolute = true;
$path = substr($path, 1);
}

$up = false;
foreach (explode('/', $path) as $chunk) {
if ('..' === $chunk && ($absolute || $up)) {
array_pop($parts);
$up = !(empty($parts) || '..' === end($parts));
} elseif ('.' !== $chunk && '' !== $chunk) {
$parts[] = $chunk;
$up = '..' !== $chunk;
}
}

return $prefix.($absolute ? '/' : '').implode('/', $parts);
}







public static function isLocalPath($path)
{
return (bool) preg_match('{^(file://|/|[a-z]:[\\\\/]|\.\.[\\\\/]|[a-z0-9_.-]+[\\\\/])}i', $path);
}

public static function getPlatformPath($path)
{
if (Platform::isWindows()) {
$path = preg_replace('{^(?:file:///([a-z])/)}i', 'file://$1:/', $path);
}

return preg_replace('{^file://}i', '', $path);
}

protected function directorySize($directory)
{
$it = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
$ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

$size = 0;
foreach ($ri as $file) {
if ($file->isFile()) {
$size += $file->getSize();
}
}

return $size;
}

protected function getProcess()
{
return new ProcessExecutor;
}










private function unlinkImplementation($path)
{
if (Platform::isWindows() && is_dir($path) && is_link($path)) {
return rmdir($path);
}

return unlink($path);
}








public function relativeSymlink($target, $link)
{
$cwd = getcwd();

$relativePath = $this->findShortestPath($link, $target);
chdir(dirname($link));
$result = @symlink($relativePath, $link);

chdir($cwd);

return (bool) $result;
}








public function isSymlinkedDirectory($directory)
{
if (!is_dir($directory)) {
return false;
}

$resolved = $this->resolveSymlinkedDirectorySymlink($directory);

return is_link($resolved);
}






private function unlinkSymlinkedDirectory($directory)
{
$resolved = $this->resolveSymlinkedDirectorySymlink($directory);

return $this->unlink($resolved);
}








private function resolveSymlinkedDirectorySymlink($pathname)
{
if (!is_dir($pathname)) {
return $pathname;
}

$resolved = rtrim($pathname, '/');

if (!strlen($resolved)) {
return $pathname;
}

return $resolved;
}







public function junction($target, $junction)
{
if (!Platform::isWindows()) {
throw new \LogicException(sprintf('Function %s is not available on non-Windows platform', __CLASS__));
}
if (!is_dir($target)) {
throw new IOException(sprintf('Cannot junction to "%s" as it is not a directory.', $target), 0, null, $target);
}
$cmd = sprintf('mklink /J %s %s',
ProcessExecutor::escape(str_replace('/', DIRECTORY_SEPARATOR, $junction)),
ProcessExecutor::escape(realpath($target)));
if ($this->getProcess()->execute($cmd, $output) !== 0) {
throw new IOException(sprintf('Failed to create junction to "%s" at "%s".', $target, $junction), 0, null, $target);
}
}







public function isJunction($junction)
{
if (!Platform::isWindows()) {
return false;
}
if (!is_dir($junction) || is_link($junction)) {
return false;
}

 $stat = lstat($junction);
return ($stat['mode'] === 0);
}







public function removeJunction($junction)
{
if (!Platform::isWindows()) {
return false;
}
$junction = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $junction), DIRECTORY_SEPARATOR);
if (!$this->isJunction($junction)) {
throw new IOException(sprintf('%s is not a junction and thus cannot be removed as one', $junction));
}
$cmd = sprintf('rmdir /S /Q %s', ProcessExecutor::escape($junction));
return ($this->getProcess()->execute($cmd, $output) === 0);
}
}
