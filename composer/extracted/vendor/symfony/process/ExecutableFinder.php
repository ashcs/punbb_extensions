<?php










namespace Symfony\Component\Process;







class ExecutableFinder
{
private $suffixes = array('.exe', '.bat', '.cmd', '.com');






public function setSuffixes(array $suffixes)
{
$this->suffixes = $suffixes;
}






public function addSuffix($suffix)
{
$this->suffixes[] = $suffix;
}










public function find($name, $default = null, array $extraDirs = array())
{
if (ini_get('open_basedir')) {
$searchPath = explode(PATH_SEPARATOR, ini_get('open_basedir'));
$dirs = array();
foreach ($searchPath as $path) {

 if (@is_dir($path)) {
$dirs[] = $path;
} else {
if (basename($path) == $name && is_executable($path)) {
return $path;
}
}
}
} else {
$dirs = array_merge(
explode(PATH_SEPARATOR, getenv('PATH') ?: getenv('Path')),
$extraDirs
);
}

$suffixes = array('');
if ('\\' === DIRECTORY_SEPARATOR) {
$pathExt = getenv('PATHEXT');
$suffixes = $pathExt ? explode(PATH_SEPARATOR, $pathExt) : $this->suffixes;
}
foreach ($suffixes as $suffix) {
foreach ($dirs as $dir) {
if (is_file($file = $dir.DIRECTORY_SEPARATOR.$name.$suffix) && ('\\' === DIRECTORY_SEPARATOR || is_executable($file))) {
return $file;
}
}
}

return $default;
}
}
