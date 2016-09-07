<?php











namespace Composer\Config;

use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Util\Silencer;







class JsonConfigSource implements ConfigSourceInterface
{



private $file;




private $authConfig;







public function __construct(JsonFile $file, $authConfig = false)
{
$this->file = $file;
$this->authConfig = $authConfig;
}




public function getName()
{
return $this->file->getPath();
}




public function addRepository($name, $config)
{
$this->manipulateJson('addRepository', $name, $config, function (&$config, $repo, $repoConfig) {
$config['repositories'][$repo] = $repoConfig;
});
}




public function removeRepository($name)
{
$this->manipulateJson('removeRepository', $name, function (&$config, $repo) {
unset($config['repositories'][$repo]);
});
}




public function addConfigSetting($name, $value)
{
$this->manipulateJson('addConfigSetting', $name, $value, function (&$config, $key, $val) {
if (preg_match('{^(github-oauth|gitlab-oauth|http-basic|platform)\.}', $key)) {
list($key, $host) = explode('.', $key, 2);
if ($this->authConfig) {
$config[$key][$host] = $val;
} else {
$config['config'][$key][$host] = $val;
}
} else {
$config['config'][$key] = $val;
}
});
}




public function removeConfigSetting($name)
{
$this->manipulateJson('removeConfigSetting', $name, function (&$config, $key) {
if (preg_match('{^(github-oauth|gitlab-oauth|http-basic|platform)\.}', $key)) {
list($key, $host) = explode('.', $key, 2);
if ($this->authConfig) {
unset($config[$key][$host]);
} else {
unset($config['config'][$key][$host]);
}
} else {
unset($config['config'][$key]);
}
});
}




public function addLink($type, $name, $value)
{
$this->manipulateJson('addLink', $type, $name, $value, function (&$config, $type, $name, $value) {
$config[$type][$name] = $value;
});
}




public function removeLink($type, $name)
{
$this->manipulateJson('removeSubNode', $type, $name, function (&$config, $type, $name) {
unset($config[$type][$name]);
});
}

protected function manipulateJson($method, $args, $fallback)
{
$args = func_get_args();

 array_shift($args);
$fallback = array_pop($args);

if ($this->file->exists()) {
if (!is_writable($this->file->getPath())) {
throw new \RuntimeException(sprintf('The file "%s" is not writable.', $this->file->getPath()));
}

if (!is_readable($this->file->getPath())) {
throw new \RuntimeException(sprintf('The file "%s" is not readable.', $this->file->getPath()));
}

$contents = file_get_contents($this->file->getPath());
} elseif ($this->authConfig) {
$contents = "{\n}\n";
} else {
$contents = "{\n    \"config\": {\n    }\n}\n";
}

$manipulator = new JsonManipulator($contents);

$newFile = !$this->file->exists();


 if ($this->authConfig && $method === 'addConfigSetting') {
$method = 'addSubNode';
list($mainNode, $name) = explode('.', $args[0], 2);
$args = array($mainNode, $name, $args[1]);
} elseif ($this->authConfig && $method === 'removeConfigSetting') {
$method = 'removeSubNode';
list($mainNode, $name) = explode('.', $args[0], 2);
$args = array($mainNode, $name);
}


 if (call_user_func_array(array($manipulator, $method), $args)) {
file_put_contents($this->file->getPath(), $manipulator->getContents());
} else {

 $config = $this->file->read();
$this->arrayUnshiftRef($args, $config);
call_user_func_array($fallback, $args);
$this->file->write($config);
}

if ($newFile) {
Silencer::call('chmod', $this->file->getPath(), 0600);
}
}








private function arrayUnshiftRef(&$array, &$value)
{
$return = array_unshift($array, '');
$array[0] = &$value;

return $return;
}
}
