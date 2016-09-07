<?php











namespace Composer\Repository;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Locker;
use Composer\Package\Version\VersionGuesser;
use Composer\Semver\VersionParser;
use Composer\Util\ProcessExecutor;





























class PathRepository extends ArrayRepository implements ConfigurableRepositoryInterface
{



private $loader;




private $versionGuesser;




private $url;




private $repoConfig;




private $process;








public function __construct(array $repoConfig, IOInterface $io, Config $config)
{
if (!isset($repoConfig['url'])) {
throw new \RuntimeException('You must specify the `url` configuration for the path repository');
}

$this->loader = new ArrayLoader();
$this->url = $repoConfig['url'];
$this->process = new ProcessExecutor($io);
$this->versionGuesser = new VersionGuesser($config, $this->process, new VersionParser());
$this->repoConfig = $repoConfig;

parent::__construct();
}

public function getRepoConfig()
{
return $this->repoConfig;
}






protected function initialize()
{
parent::initialize();

foreach ($this->getUrlMatches() as $url) {
$path = realpath($url) . DIRECTORY_SEPARATOR;
$composerFilePath = $path.'composer.json';

if (!file_exists($composerFilePath)) {
continue;
}

$json = file_get_contents($composerFilePath);
$package = JsonFile::parseJson($json, $composerFilePath);
$package['dist'] = array(
'type' => 'path',
'url' => $url,
'reference' => sha1($json),
);

if (!isset($package['version'])) {
$package['version'] = $this->versionGuesser->guessVersion($package, $path) ?: 'dev-master';
}

$output = '';
if (is_dir($path . DIRECTORY_SEPARATOR . '.git') && 0 === $this->process->execute('git log -n1 --pretty=%H', $output, $path)) {
$package['dist']['reference'] = trim($output);
}

$package = $this->loader->load($package);
$this->addPackage($package);
}
}






private function getUrlMatches()
{

 return array_map(function ($val) {
return str_replace(DIRECTORY_SEPARATOR, '/', $val);
}, glob($this->url, GLOB_MARK | GLOB_ONLYDIR));
}
}
