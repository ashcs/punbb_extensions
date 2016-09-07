<?php











namespace Composer\Repository\Vcs;

use Composer\Cache;
use Composer\Config;
use Composer\Json\JsonFile;
use Composer\IO\IOInterface;




class HgBitbucketDriver extends VcsDriver
{
protected $cache;
protected $owner;
protected $repository;
protected $tags;
protected $branches;
protected $rootIdentifier;
protected $infoCache = array();




public function initialize()
{
preg_match('#^https?://bitbucket\.org/([^/]+)/([^/]+)/?$#', $this->url, $match);
$this->owner = $match[1];
$this->repository = $match[2];
$this->originUrl = 'bitbucket.org';
$this->cache = new Cache($this->io, $this->config->get('cache-repo-dir').'/'.$this->originUrl.'/'.$this->owner.'/'.$this->repository);
}




public function getRootIdentifier()
{
if (null === $this->rootIdentifier) {
$resource = $this->getScheme() . '://bitbucket.org/api/1.0/repositories/'.$this->owner.'/'.$this->repository.'/tags';
$repoData = JsonFile::parseJson($this->getContents($resource), $resource);
if (array() === $repoData || !isset($repoData['tip'])) {
throw new \RuntimeException($this->url.' does not appear to be a mercurial repository, use '.$this->url.'.git if this is a git bitbucket repository');
}
$this->rootIdentifier = $repoData['tip']['raw_node'];
}

return $this->rootIdentifier;
}




public function getUrl()
{
return $this->url;
}




public function getSource($identifier)
{
return array('type' => 'hg', 'url' => $this->getUrl(), 'reference' => $identifier);
}




public function getDist($identifier)
{
$url = $this->getScheme() . '://bitbucket.org/'.$this->owner.'/'.$this->repository.'/get/'.$identifier.'.zip';

return array('type' => 'zip', 'url' => $url, 'reference' => $identifier, 'shasum' => '');
}




public function getComposerInformation($identifier)
{
if (preg_match('{[a-f0-9]{40}}i', $identifier) && $res = $this->cache->read($identifier)) {
$this->infoCache[$identifier] = JsonFile::parseJson($res);
}

if (!isset($this->infoCache[$identifier])) {
$resource = $this->getScheme() . '://bitbucket.org/api/1.0/repositories/'.$this->owner.'/'.$this->repository.'/src/'.$identifier.'/composer.json';
$repoData = JsonFile::parseJson($this->getContents($resource), $resource);


 
 
 

if (!array_key_exists('data', $repoData)) {
return;
}

$composer = JsonFile::parseJson($repoData['data'], $resource);

if (empty($composer['time'])) {
$resource = $this->getScheme() . '://bitbucket.org/api/1.0/repositories/'.$this->owner.'/'.$this->repository.'/changesets/'.$identifier;
$changeset = JsonFile::parseJson($this->getContents($resource), $resource);
$composer['time'] = $changeset['timestamp'];
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
$resource = $this->getScheme() . '://bitbucket.org/api/1.0/repositories/'.$this->owner.'/'.$this->repository.'/tags';
$tagsData = JsonFile::parseJson($this->getContents($resource), $resource);
$this->tags = array();
foreach ($tagsData as $tag => $data) {
$this->tags[$tag] = $data['raw_node'];
}
unset($this->tags['tip']);
}

return $this->tags;
}




public function getBranches()
{
if (null === $this->branches) {
$resource = $this->getScheme() . '://bitbucket.org/api/1.0/repositories/'.$this->owner.'/'.$this->repository.'/branches';
$branchData = JsonFile::parseJson($this->getContents($resource), $resource);
$this->branches = array();
foreach ($branchData as $branch => $data) {
$this->branches[$branch] = $data['raw_node'];
}
}

return $this->branches;
}




public static function supports(IOInterface $io, Config $config, $url, $deep = false)
{
if (!preg_match('#^https?://bitbucket\.org/([^/]+)/([^/]+)/?$#', $url)) {
return false;
}

if (!extension_loaded('openssl')) {
$io->writeError('Skipping Bitbucket hg driver for '.$url.' because the OpenSSL PHP extension is missing.', true, IOInterface::VERBOSE);

return false;
}

return true;
}
}
