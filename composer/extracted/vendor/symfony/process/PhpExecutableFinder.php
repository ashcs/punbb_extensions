<?php










namespace Symfony\Component\Process;







class PhpExecutableFinder
{
private $executableFinder;

public function __construct()
{
$this->executableFinder = new ExecutableFinder();
}








public function find($includeArgs = true)
{
$args = $this->findArguments();
$args = $includeArgs && $args ? ' '.implode(' ', $args) : '';


 if (defined('HHVM_VERSION')) {
return (getenv('PHP_BINARY') ?: PHP_BINARY).$args;
}


 if (defined('PHP_BINARY') && PHP_BINARY && in_array(PHP_SAPI, array('cli', 'cli-server', 'phpdbg')) && is_file(PHP_BINARY)) {
return PHP_BINARY.$args;
}

if ($php = getenv('PHP_PATH')) {
if (!is_executable($php)) {
return false;
}

return $php;
}

if ($php = getenv('PHP_PEAR_PHP_BIN')) {
if (is_executable($php)) {
return $php;
}
}

$dirs = array(PHP_BINDIR);
if ('\\' === DIRECTORY_SEPARATOR) {
$dirs[] = 'C:\xampp\php\\';
}

return $this->executableFinder->find('php', false, $dirs);
}






public function findArguments()
{
$arguments = array();

if (defined('HHVM_VERSION')) {
$arguments[] = '--php';
} elseif ('phpdbg' === PHP_SAPI) {
$arguments[] = '-qrr';
}

return $arguments;
}
}
