<?php











namespace Composer\Util;

use Composer\IO\IOInterface;






class ErrorHandler
{
private static $io;












public static function handle($level, $message, $file, $line)
{

 if (!(error_reporting() & $level)) {
return;
}

if (ini_get('xdebug.scream')) {
$message .= "\n\nWarning: You have xdebug.scream enabled, the warning above may be".
"\na legitimately suppressed error that you were not supposed to see.";
}

if ($level !== E_DEPRECATED && $level !== E_USER_DEPRECATED) {
throw new \ErrorException($message, 0, $level, $file, $line);
}

if (self::$io) {
self::$io->writeError('<warning>Deprecation Notice: '.$message.' in '.$file.':'.$line.'</warning>');
if (self::$io->isVerbose()) {
self::$io->writeError('<warning>Stack trace:</warning>');
self::$io->writeError(array_filter(array_map(function ($a) {
if (isset($a['line'], $a['file'])) {
return '<warning> '.$a['file'].':'.$a['line'].'</warning>';
}

return null;
}, array_slice(debug_backtrace(), 2))));
}
}
}






public static function register(IOInterface $io = null)
{
set_error_handler(array(__CLASS__, 'handle'));
error_reporting(E_ALL | E_STRICT);
self::$io = $io;
}
}
