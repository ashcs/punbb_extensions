<?php










namespace Symfony\Component\Filesystem;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;






class Filesystem
{














public function copy($originFile, $targetFile, $override = false)
{
if (stream_is_local($originFile) && !is_file($originFile)) {
throw new FileNotFoundException(sprintf('Failed to copy "%s" because file does not exist.', $originFile), 0, null, $originFile);
}

$this->mkdir(dirname($targetFile));

$doCopy = true;
if (!$override && null === parse_url($originFile, PHP_URL_HOST) && is_file($targetFile)) {
$doCopy = filemtime($originFile) > filemtime($targetFile);
}

if ($doCopy) {

 if (false === $source = @fopen($originFile, 'r')) {
throw new IOException(sprintf('Failed to copy "%s" to "%s" because source file could not be opened for reading.', $originFile, $targetFile), 0, null, $originFile);
}


 if (false === $target = @fopen($targetFile, 'w', null, stream_context_create(array('ftp' => array('overwrite' => true))))) {
throw new IOException(sprintf('Failed to copy "%s" to "%s" because target file could not be opened for writing.', $originFile, $targetFile), 0, null, $originFile);
}

$bytesCopied = stream_copy_to_stream($source, $target);
fclose($source);
fclose($target);
unset($source, $target);

if (!is_file($targetFile)) {
throw new IOException(sprintf('Failed to copy "%s" to "%s".', $originFile, $targetFile), 0, null, $originFile);
}


 @chmod($targetFile, fileperms($targetFile) | (fileperms($originFile) & 0111));

if (stream_is_local($originFile) && $bytesCopied !== ($bytesOrigin = filesize($originFile))) {
throw new IOException(sprintf('Failed to copy the whole content of "%s" to "%s" (%g of %g bytes copied).', $originFile, $targetFile, $bytesCopied, $bytesOrigin), 0, null, $originFile);
}
}
}









public function mkdir($dirs, $mode = 0777)
{
foreach ($this->toIterator($dirs) as $dir) {
if (is_dir($dir)) {
continue;
}

if (true !== @mkdir($dir, $mode, true)) {
$error = error_get_last();
if (!is_dir($dir)) {

 if ($error) {
throw new IOException(sprintf('Failed to create "%s": %s.', $dir, $error['message']), 0, null, $dir);
}
throw new IOException(sprintf('Failed to create "%s"', $dir), 0, null, $dir);
}
}
}
}








public function exists($files)
{
foreach ($this->toIterator($files) as $file) {
if (!file_exists($file)) {
return false;
}
}

return true;
}










public function touch($files, $time = null, $atime = null)
{
foreach ($this->toIterator($files) as $file) {
$touch = $time ? @touch($file, $time, $atime) : @touch($file);
if (true !== $touch) {
throw new IOException(sprintf('Failed to touch "%s".', $file), 0, null, $file);
}
}
}








public function remove($files)
{
$files = iterator_to_array($this->toIterator($files));
$files = array_reverse($files);
foreach ($files as $file) {
if (!file_exists($file) && !is_link($file)) {
continue;
}

if (is_dir($file) && !is_link($file)) {
$this->remove(new \FilesystemIterator($file));

if (true !== @rmdir($file)) {
throw new IOException(sprintf('Failed to remove directory "%s".', $file), 0, null, $file);
}
} else {

 if ('\\' === DIRECTORY_SEPARATOR && is_dir($file)) {
if (true !== @rmdir($file)) {
throw new IOException(sprintf('Failed to remove file "%s".', $file), 0, null, $file);
}
} else {
if (true !== @unlink($file)) {
throw new IOException(sprintf('Failed to remove file "%s".', $file), 0, null, $file);
}
}
}
}
}











public function chmod($files, $mode, $umask = 0000, $recursive = false)
{
foreach ($this->toIterator($files) as $file) {
if (true !== @chmod($file, $mode & ~$umask)) {
throw new IOException(sprintf('Failed to chmod file "%s".', $file), 0, null, $file);
}
if ($recursive && is_dir($file) && !is_link($file)) {
$this->chmod(new \FilesystemIterator($file), $mode, $umask, true);
}
}
}










public function chown($files, $user, $recursive = false)
{
foreach ($this->toIterator($files) as $file) {
if ($recursive && is_dir($file) && !is_link($file)) {
$this->chown(new \FilesystemIterator($file), $user, true);
}
if (is_link($file) && function_exists('lchown')) {
if (true !== @lchown($file, $user)) {
throw new IOException(sprintf('Failed to chown file "%s".', $file), 0, null, $file);
}
} else {
if (true !== @chown($file, $user)) {
throw new IOException(sprintf('Failed to chown file "%s".', $file), 0, null, $file);
}
}
}
}










public function chgrp($files, $group, $recursive = false)
{
foreach ($this->toIterator($files) as $file) {
if ($recursive && is_dir($file) && !is_link($file)) {
$this->chgrp(new \FilesystemIterator($file), $group, true);
}
if (is_link($file) && function_exists('lchgrp')) {
if (true !== @lchgrp($file, $group) || (defined('HHVM_VERSION') && !posix_getgrnam($group))) {
throw new IOException(sprintf('Failed to chgrp file "%s".', $file), 0, null, $file);
}
} else {
if (true !== @chgrp($file, $group)) {
throw new IOException(sprintf('Failed to chgrp file "%s".', $file), 0, null, $file);
}
}
}
}











public function rename($origin, $target, $overwrite = false)
{

 if (!$overwrite && is_readable($target)) {
throw new IOException(sprintf('Cannot rename because the target "%s" already exists.', $target), 0, null, $target);
}

if (true !== @rename($origin, $target)) {
throw new IOException(sprintf('Cannot rename "%s" to "%s".', $origin, $target), 0, null, $target);
}
}










public function symlink($originDir, $targetDir, $copyOnWindows = false)
{
if ('\\' === DIRECTORY_SEPARATOR && $copyOnWindows) {
$this->mirror($originDir, $targetDir);

return;
}

$this->mkdir(dirname($targetDir));

$ok = false;
if (is_link($targetDir)) {
if (readlink($targetDir) != $originDir) {
$this->remove($targetDir);
} else {
$ok = true;
}
}

if (!$ok && true !== @symlink($originDir, $targetDir)) {
$report = error_get_last();
if (is_array($report)) {
if ('\\' === DIRECTORY_SEPARATOR && false !== strpos($report['message'], 'error code(1314)')) {
throw new IOException('Unable to create symlink due to error code 1314: \'A required privilege is not held by the client\'. Do you have the required Administrator-rights?', 0, null, $targetDir);
}
}
throw new IOException(sprintf('Failed to create symbolic link from "%s" to "%s".', $originDir, $targetDir), 0, null, $targetDir);
}
}









public function makePathRelative($endPath, $startPath)
{

 if ('\\' === DIRECTORY_SEPARATOR) {
$endPath = str_replace('\\', '/', $endPath);
$startPath = str_replace('\\', '/', $startPath);
}


 $startPathArr = explode('/', trim($startPath, '/'));
$endPathArr = explode('/', trim($endPath, '/'));


 $index = 0;
while (isset($startPathArr[$index]) && isset($endPathArr[$index]) && $startPathArr[$index] === $endPathArr[$index]) {
++$index;
}


 $depth = count($startPathArr) - $index;


 if ('/' === $startPath[0] && 0 === $index && 1 === $depth) {
$traverser = '';
} else {

 $traverser = str_repeat('../', $depth);
}

$endPathRemainder = implode('/', array_slice($endPathArr, $index));


 $relativePath = $traverser.('' !== $endPathRemainder ? $endPathRemainder.'/' : '');

return '' === $relativePath ? './' : $relativePath;
}















public function mirror($originDir, $targetDir, \Traversable $iterator = null, $options = array())
{
$targetDir = rtrim($targetDir, '/\\');
$originDir = rtrim($originDir, '/\\');


 if ($this->exists($targetDir) && isset($options['delete']) && $options['delete']) {
$deleteIterator = $iterator;
if (null === $deleteIterator) {
$flags = \FilesystemIterator::SKIP_DOTS;
$deleteIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($targetDir, $flags), \RecursiveIteratorIterator::CHILD_FIRST);
}
foreach ($deleteIterator as $file) {
$origin = str_replace($targetDir, $originDir, $file->getPathname());
if (!$this->exists($origin)) {
$this->remove($file);
}
}
}

$copyOnWindows = false;
if (isset($options['copy_on_windows'])) {
$copyOnWindows = $options['copy_on_windows'];
}

if (null === $iterator) {
$flags = $copyOnWindows ? \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS : \FilesystemIterator::SKIP_DOTS;
$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($originDir, $flags), \RecursiveIteratorIterator::SELF_FIRST);
}

if ($this->exists($originDir)) {
$this->mkdir($targetDir);
}

foreach ($iterator as $file) {
$target = str_replace($originDir, $targetDir, $file->getPathname());

if ($copyOnWindows) {
if (is_link($file) || is_file($file)) {
$this->copy($file, $target, isset($options['override']) ? $options['override'] : false);
} elseif (is_dir($file)) {
$this->mkdir($target);
} else {
throw new IOException(sprintf('Unable to guess "%s" file type.', $file), 0, null, $file);
}
} else {
if (is_link($file)) {
$this->symlink($file->getLinkTarget(), $target);
} elseif (is_dir($file)) {
$this->mkdir($target);
} elseif (is_file($file)) {
$this->copy($file, $target, isset($options['override']) ? $options['override'] : false);
} else {
throw new IOException(sprintf('Unable to guess "%s" file type.', $file), 0, null, $file);
}
}
}
}








public function isAbsolutePath($file)
{
return strspn($file, '/\\', 0, 1)
|| (strlen($file) > 3 && ctype_alpha($file[0])
&& substr($file, 1, 1) === ':'
&& strspn($file, '/\\', 2, 1)
)
|| null !== parse_url($file, PHP_URL_SCHEME)
;
}










public function tempnam($dir, $prefix)
{
list($scheme, $hierarchy) = $this->getSchemeAndHierarchy($dir);


 if (null === $scheme || 'file' === $scheme) {
$tmpFile = tempnam($hierarchy, $prefix);


 if (false !== $tmpFile) {
if (null !== $scheme) {
return $scheme.'://'.$tmpFile;
}

return $tmpFile;
}

throw new IOException('A temporary file could not be created.');
}


 for ($i = 0; $i < 10; ++$i) {

 $tmpFile = $dir.'/'.$prefix.uniqid(mt_rand(), true);


 
 $handle = @fopen($tmpFile, 'x+');


 if (false === $handle) {
continue;
}


 @fclose($handle);

return $tmpFile;
}

throw new IOException('A temporary file could not be created.');
}











public function dumpFile($filename, $content, $mode = 0666)
{
$dir = dirname($filename);

if (!is_dir($dir)) {
$this->mkdir($dir);
} elseif (!is_writable($dir)) {
throw new IOException(sprintf('Unable to write to the "%s" directory.', $dir), 0, null, $dir);
}

$tmpFile = $this->tempnam($dir, basename($filename));

if (false === @file_put_contents($tmpFile, $content)) {
throw new IOException(sprintf('Failed to write file "%s".', $filename), 0, null, $filename);
}

if (null !== $mode) {
if (func_num_args() > 2) {
@trigger_error('Support for modifying file permissions is deprecated since version 2.3.12 and will be removed in 3.0.', E_USER_DEPRECATED);
}

$this->chmod($tmpFile, $mode);
}
$this->rename($tmpFile, $filename, true);
}






private function toIterator($files)
{
if (!$files instanceof \Traversable) {
$files = new \ArrayObject(is_array($files) ? $files : array($files));
}

return $files;
}








private function getSchemeAndHierarchy($filename)
{
$components = explode('://', $filename, 2);

return 2 === count($components) ? array($components[0], $components[1]) : array(null, $components[0]);
}
}
