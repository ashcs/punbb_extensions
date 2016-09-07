<?php











namespace Composer\Repository\Vcs;

use Composer\Config;
use Composer\Cache;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Downloader\TransportException;
use Composer\Util\RemoteFilesystem;
use Composer\Util\GitLab;







class GitLabDriver extends VcsDriver
{
private $scheme;
private $owner;
private $repository;

private $cache;
private $infoCache = array();




private $project;




private $commits = array();




private $tags;




private $branches;






protected $gitDriver;

const URL_REGEX = '#^(?:(?P<scheme>https?)://(?P<domain>.+?)/|git@(?P<domain2>[^:]+):)(?P<owner>[^/]+)/(?P<repo>[^/]+?)(?:\.git|/)?$#';







public function initialize()
{
if (!preg_match(self::URL_REGEX, $this->url, $match)) {
throw new \InvalidArgumentException('The URL provided is invalid. It must be the HTTP URL of a GitLab project.');
}

$this->scheme = !empty($match['scheme']) ? $match['scheme'] : 'https';
$this->originUrl = !empty($match['domain']) ? $match['domain'] : $match['domain2'];
$this->owner = $match['owner'];
$this->repository = preg_replace('#(\.git)$#', '', $match['repo']);

$this->cache = new Cache($this->io, $this->config->get('cache-repo-dir').'/'.$this->originUrl.'/'.$this->owner.'/'.$this->repository);

$this->fetchProject();
}







public function setRemoteFilesystem(RemoteFilesystem $remoteFilesystem)
{
$this->remoteFilesystem = $remoteFilesystem;
}








public function getComposerInformation($identifier)
{

 if (!preg_match('{[a-f0-9]{40}}i', $identifier)) {
$branches = $this->getBranches();
if (isset($branches[$identifier])) {
$identifier = $branches[$identifier];
}
}

if (isset($this->infoCache[$identifier])) {
return $this->infoCache[$identifier];
}

if (preg_match('{[a-f0-9]{40}}i', $identifier) && $res = $this->cache->read($identifier)) {
return $this->infoCache[$identifier] = JsonFile::parseJson($res, $res);
}

try {
$composer = $this->fetchComposerFile($identifier);
} catch (TransportException $e) {
if ($e->getCode() !== 404) {
throw $e;
}
$composer = false;
}

if ($composer && !isset($composer['time']) && isset($this->commits[$identifier])) {
$composer['time'] = $this->commits[$identifier]['committed_date'];
}

if (preg_match('{[a-f0-9]{40}}i', $identifier)) {
$this->cache->write($identifier, json_encode($composer));
}

return $this->infoCache[$identifier] = $composer;
}




public function getRepositoryUrl()
{
return $this->project['ssh_url_to_repo'];
}




public function getUrl()
{
return $this->project['web_url'];
}




public function getDist($identifier)
{
$url = $this->getApiUrl().'/repository/archive.zip?sha='.$identifier;

return array('type' => 'zip', 'url' => $url, 'reference' => $identifier, 'shasum' => '');
}




public function getSource($identifier)
{
return array('type' => 'git', 'url' => $this->getRepositoryUrl(), 'reference' => $identifier);
}




public function getRootIdentifier()
{
return $this->project['default_branch'];
}




public function getBranches()
{
if (!$this->branches) {
$this->branches = $this->getReferences('branches');
}

return $this->branches;
}




public function getTags()
{
if (!$this->tags) {
$this->tags = $this->getReferences('tags');
}

return $this->tags;
}








protected function fetchComposerFile($identifier)
{
$resource = $this->getApiUrl().'/repository/blobs/'.$identifier.'?filepath=composer.json';

return JsonFile::parseJson($this->getContents($resource), $resource);
}




public function getApiUrl()
{
return $this->scheme.'://'.$this->originUrl.'/api/v3/projects/'.$this->owner.'%2F'.$this->repository;
}






protected function getReferences($type)
{
$resource = $this->getApiUrl().'/repository/'.$type;

$data = JsonFile::parseJson($this->getContents($resource), $resource);

$references = array();

foreach ($data as $datum) {
$references[$datum['name']] = $datum['commit']['id'];


 
 $this->commits[$datum['commit']['id']] = $datum['commit'];
}

return $references;
}

protected function fetchProject()
{

 $resource = $this->getApiUrl();
$this->project = JsonFile::parseJson($this->getContents($resource, true), $resource);
}

protected function attemptCloneFallback()
{
try {

 
 
 $this->setupGitDriver($this->generateSshUrl());

return;
} catch (\RuntimeException $e) {
$this->gitDriver = null;

$this->io->writeError('<error>Failed to clone the '.$this->generateSshUrl().' repository, try running in interactive mode so that you can enter your credentials</error>');
throw $e;
}
}






protected function generateSshUrl()
{
return 'git@' . $this->originUrl . ':'.$this->owner.'/'.$this->repository.'.git';
}

protected function setupGitDriver($url)
{
$this->gitDriver = new GitDriver(
array('url' => $url),
$this->io,
$this->config,
$this->process,
$this->remoteFilesystem
);
$this->gitDriver->initialize();
}




protected function getContents($url, $fetchingRepoData = false)
{
try {
return parent::getContents($url);
} catch (TransportException $e) {
$gitLabUtil = new GitLab($this->io, $this->config, $this->process, $this->remoteFilesystem);

switch ($e->getCode()) {
case 401:
case 404:

 if (!$fetchingRepoData) {
throw $e;
}

if ($gitLabUtil->authorizeOAuth($this->originUrl)) {
return parent::getContents($url);
}

if (!$this->io->isInteractive()) {
return $this->attemptCloneFallback();
}
$this->io->writeError('<warning>Failed to download ' . $this->owner . '/' . $this->repository . ':' . $e->getMessage() . '</warning>');
$gitLabUtil->authorizeOAuthInteractively($this->originUrl, 'Your credentials are required to fetch private repository metadata (<info>'.$this->url.'</info>)');

return parent::getContents($url);

case 403:
if (!$this->io->hasAuthentication($this->originUrl) && $gitLabUtil->authorizeOAuth($this->originUrl)) {
return parent::getContents($url);
}

if (!$this->io->isInteractive() && $fetchingRepoData) {
return $this->attemptCloneFallback();
}

throw $e;

default:
throw $e;
}
}
}







public static function supports(IOInterface $io, Config $config, $url, $deep = false)
{
if (!preg_match(self::URL_REGEX, $url, $match)) {
return false;
}

$scheme = !empty($match['scheme']) ? $match['scheme'] : 'https';
$originUrl = !empty($match['domain']) ? $match['domain'] : $match['domain2'];

if (!in_array($originUrl, (array) $config->get('gitlab-domains'))) {
return false;
}

if ('https' === $scheme && !extension_loaded('openssl')) {
$io->writeError('Skipping GitLab driver for '.$url.' because the OpenSSL PHP extension is missing.', true, IOInterface::VERBOSE);

return false;
}

return true;
}
}
