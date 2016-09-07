<?php

















namespace Composer\Autoload;

use Composer\Util\Silencer;
use Symfony\Component\Finder\Finder;
use Composer\IO\IOInterface;







class ClassMapGenerator
{






public static function dump($dirs, $file)
{
$maps = array();

foreach ($dirs as $dir) {
$maps = array_merge($maps, static::createMap($dir));
}

file_put_contents($file, sprintf('<?php return %s;', var_export($maps, true)));
}












public static function createMap($path, $blacklist = null, IOInterface $io = null, $namespace = null)
{
if (is_string($path)) {
if (is_file($path)) {
$path = array(new \SplFileInfo($path));
} elseif (is_dir($path)) {
$path = Finder::create()->files()->followLinks()->name('/\.(php|inc|hh)$/')->in($path);
} else {
throw new \RuntimeException(
'Could not scan for classes inside "'.$path.
'" which does not appear to be a file nor a folder'
);
}
}

$map = array();

foreach ($path as $file) {
$filePath = $file->getRealPath();

if (!in_array(pathinfo($filePath, PATHINFO_EXTENSION), array('php', 'inc', 'hh'))) {
continue;
}

if ($blacklist && preg_match($blacklist, strtr($filePath, '\\', '/'))) {
continue;
}

$classes = self::findClasses($filePath);

foreach ($classes as $class) {

 if (null !== $namespace && 0 !== strpos($class, $namespace)) {
continue;
}

if (!isset($map[$class])) {
$map[$class] = $filePath;
} elseif ($io && $map[$class] !== $filePath && !preg_match('{/(test|fixture|example|stub)s?/}i', strtr($map[$class].' '.$filePath, '\\', '/'))) {
$io->writeError(
'<warning>Warning: Ambiguous class resolution, "'.$class.'"'.
' was found in both "'.$map[$class].'" and "'.$filePath.'", the first will be used.</warning>'
);
}
}
}

return $map;
}








private static function findClasses($path)
{
$extraTypes = PHP_VERSION_ID < 50400 ? '' : '|trait';
if (defined('HHVM_VERSION') && version_compare(HHVM_VERSION, '3.3', '>=')) {
$extraTypes .= '|enum';
}


 
 $contents = @php_strip_whitespace($path);
if (!$contents) {
if (!file_exists($path)) {
$message = 'File at "%s" does not exist, check your classmap definitions';
} elseif (!is_readable($path)) {
$message = 'File at "%s" is not readable, check its permissions';
} elseif ('' === trim(file_get_contents($path))) {

 return array();
} else {
$message = 'File at "%s" could not be parsed as PHP, it may be binary or corrupted';
}
$error = error_get_last();
if (isset($error['message'])) {
$message .= PHP_EOL . 'The following message may be helpful:' . PHP_EOL . $error['message'];
}
throw new \RuntimeException(sprintf($message, $path));
}


 if (!preg_match('{\b(?:class|interface'.$extraTypes.')\s}i', $contents)) {
return array();
}


 $contents = preg_replace('{<<<\s*(\'?)(\w+)\\1(?:\r\n|\n|\r)(?:.*?)(?:\r\n|\n|\r)\\2(?=\r\n|\n|\r|;)}s', 'null', $contents);

 $contents = preg_replace('{"[^"\\\\]*+(\\\\.[^"\\\\]*+)*+"|\'[^\'\\\\]*+(\\\\.[^\'\\\\]*+)*+\'}s', 'null', $contents);

 if (substr($contents, 0, 2) !== '<?') {
$contents = preg_replace('{^.+?<\?}s', '<?', $contents, 1, $replacements);
if ($replacements === 0) {
return array();
}
}

 $contents = preg_replace('{\?>.+<\?}s', '?><?', $contents);

 $pos = strrpos($contents, '?>');
if (false !== $pos && false === strpos(substr($contents, $pos), '<?')) {
$contents = substr($contents, 0, $pos);
}

preg_match_all('{
            (?:
                 \b(?<![\$:>])(?P<type>class|interface'.$extraTypes.') \s++ (?P<name>[a-zA-Z_\x7f-\xff:][a-zA-Z0-9_\x7f-\xff:\-]*+)
               | \b(?<![\$:>])(?P<ns>namespace) (?P<nsname>\s++[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\s*+\\\\\s*+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+)? \s*+ [\{;]
            )
        }ix', $contents, $matches);

$classes = array();
$namespace = '';

for ($i = 0, $len = count($matches['type']); $i < $len; $i++) {
if (!empty($matches['ns'][$i])) {
$namespace = str_replace(array(' ', "\t", "\r", "\n"), '', $matches['nsname'][$i]) . '\\';
} else {
$name = $matches['name'][$i];
if ($name[0] === ':') {

 $name = 'xhp'.substr(str_replace(array('-', ':'), array('_', '__'), $name), 1);
} elseif ($matches['type'][$i] === 'enum') {

 
 
 
 $name = rtrim($name, ':');
}
$classes[] = ltrim($namespace . $name, '\\');
}
}

return $classes;
}
}
