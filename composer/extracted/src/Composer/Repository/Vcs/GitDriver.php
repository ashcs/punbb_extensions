<?php











namespace Composer\Repository\Vcs;

use Composer\Json\JsonFile;
use Composer\Util\ProcessExecutor;
use Composer\Util\Filesystem;
use Composer\Util\Git as GitUtil;
use Composer\IO\IOInterface;
use Composer\Cache;
use Composer\Config;




class GitDriver extends VcsDriver
{
protected $cache;
protected $tags;
protected $branches;
protected $rootIdentifier;
protected $repoDir;
protected $infoCache = array();




public function initialize()
{
if (Filesystem::isLocalPath($this->url)) {
$this->repoDir = $this->url;
$cacheUrl = realpath($this->url);
} else {
$this->repoDir = $this->config->get('cache-vcs-dir') . '/' . preg_replace('{[^a-z0-9.]}i', '-', $this->url) . '/';

GitUtil::cleanEnv();

$fs = new Filesystem();
$fs->ensureDirectoryExists(dirname($this->repoDir));

if (!is_writable(dirname($this->repoDir))) {
throw new \RuntimeException('Can not clone '.$this->url.' to access package information. The "'.dirname($this->repoDir).'" directory is not writable by the current user.');
}

if (preg_match('{^ssh://[^@]+@[^:]+:[^0-9]+}', $this->url)) {
throw new \InvalidArgumentException('The source URL '.$this->url.' is invalid, ssh URLs should have a port number after ":".'."\n".'Use ssh://git@example.com:22/path or just git@example.com:path if you do not want to provide a password or custom port.');
}

$gitUtil = new GitUtil($this->io, $this->config, $this->process, $fs);


 if (is_dir($this->repoDir) && 0 === $this->process->execute('git rev-parse --git-dir', $output, $this->repoDir) && trim($output) === '.') {
try {
$commandCallable = function ($url) {
return sprintf('git remote set-url origin %s && git remote update --prune origin', ProcessExecutor::escape($url));
};
$gitUtil->runCommand($commandCallable, $this->url, $this->repoDir);
} catch (\Exception $e) {
$this->io->writeError('<error>Failed to update '.$this->url.', package information from this repository may be outdated ('.$e->getMessage().')</error>');
}
} else {

 $fs->removeDirectory($this->repoDir);

$repoDir = $this->repoDir;
$commandCallable = function ($url) use ($repoDir) {
return sprintf('git clone --mirror %s %s', ProcessExecutor::escape($url), ProcessExecutor::escape($repoDir));
};

$gitUtil->runCommand($commandCallable, $this->url, $this->repoDir, true);
}

$cacheUrl = $this->url;
}

$this->getTags();
$this->getBranches();

$this->cache = new Cache($this->io, $this->config->get('cache-repo-dir').'/'.preg_replace('{[^a-z0-9.]}i', '-', $cacheUrl));
}




public function getRootIdentifier()
{
if (null === $this->rootIdentifier) {
$this->rootIdentifier = 'master';


 $this->process->execute('git branch --no-color', $output, $this->repoDir);
$branches = $this->process->splitLines($output);
if (!in_array('* master', $branches)) {
foreach ($branches as $branch) {
if ($branch && preg_match('{^\* +(\S+)}', $branch, $match)) {
$this->rootIdentifier = $match[1];
break;
}
}
}
}

return $this->rootIdentifier;
}




public function getUrl()
{
return $this->url;
}




public function getSource($identifier)
{
return array('type' => 'git', 'url' => $this->getUrl(), 'reference' => $identifier);
}




public function getDist($identifier)
{
return null;
}




public function getComposerInformation($identifier)
{
if (preg_match('{[a-f0-9]{40}}i', $identifier) && $res = $this->cache->read($identifier)) {
$this->infoCache[$identifier] = JsonFile::parseJson($res);
}

if (!isset($this->infoCache[$identifier])) {
$resource = sprintf('%s:composer.json', ProcessExecutor::escape($identifier));
$this->process->execute(sprintf('git show %s', $resource), $composer, $this->repoDir);

if (!trim($composer)) {
return;
}

$composer = JsonFile::parseJson($composer, $resource);

if (empty($composer['time'])) {
$this->process->execute(sprintf('git log -1 --format=%%at %s', ProcessExecutor::escape($identifier)), $output, $this->repoDir);
$date = new \DateTime('@'.trim($output), new \DateTimeZone('UTC'));
$composer['time'] = $date->format('Y-m-d H:i:s');
}

if (preg_match('{[a-f0-9]{40}}i', $identifier)) {
$this->cache->write($identifier, json_encode($composer));
}

$this->infoCache[$identifier] = $composer;
}

return $this->infoCache[$identifier];
}




public function getTags()
{
if (null === $this->tags) {
$this->tags = array();

$this->process->execute('git show-ref --tags', $output, $this->repoDir);
foreach ($output = $this->process->splitLines($output) as $tag) {
if ($tag && preg_match('{^([a-f0-9]{40}) refs/tags/(\S+)$}', $tag, $match)) {
$this->tags[$match[2]] = $match[1];
}
}
}

return $this->tags;
}




public function getBranches()
{
if (null === $this->branches) {
$branches = array();

$this->process->execute('git branch --no-color --no-abbrev -v', $output, $this->repoDir);
foreach ($this->process->splitLines($output) as $branch) {
if ($branch && !preg_match('{^ *[^/]+/HEAD }', $branch)) {
if (preg_match('{^(?:\* )? *(\S+) *([a-f0-9]+)(?: .*)?$}', $branch, $match)) {
$branches[$match[1]] = $match[2];
}
}
}

$this->branches = $branches;
}

return $this->branches;
}




public static function supports(IOInterface $io, Config $config, $url, $deep = false)
{
if (preg_match('#(^git://|\.git$|git(?:olite)?@|//git\.|//github.com/)#i', $url)) {
return true;
}


 if (Filesystem::isLocalPath($url)) {
$url = Filesystem::getPlatformPath($url);
if (!is_dir($url)) {
return false;
}

$process = new ProcessExecutor($io);

 if ($process->execute('git tag', $output, $url) === 0) {
return true;
}
}

if (!$deep) {
return false;
}

$process = new ProcessExecutor($io);
if ($process->execute('git ls-remote --heads ' . ProcessExecutor::escape($url), $output) === 0) {
return true;
}

return false;
}
}
