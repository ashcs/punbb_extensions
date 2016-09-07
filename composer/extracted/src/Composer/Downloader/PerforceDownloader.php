<?php











namespace Composer\Downloader;

use Composer\Package\PackageInterface;
use Composer\Repository\VcsRepository;
use Composer\Util\Perforce;




class PerforceDownloader extends VcsDownloader
{
protected $perforce;




public function doDownload(PackageInterface $package, $path, $url)
{
$ref = $package->getSourceReference();
$label = $this->getLabelFromSourceReference($ref);

$this->io->writeError('    Cloning ' . $ref);
$this->initPerforce($package, $path, $url);
$this->perforce->setStream($ref);
$this->perforce->p4Login($this->io);
$this->perforce->writeP4ClientSpec();
$this->perforce->connectClient();
$this->perforce->syncCodeBase($label);
$this->perforce->cleanupClientSpec();
}

private function getLabelFromSourceReference($ref)
{
$pos = strpos($ref, '@');
if (false !== $pos) {
return substr($ref, $pos + 1);
}

return null;
}

public function initPerforce($package, $path, $url)
{
if (!empty($this->perforce)) {
$this->perforce->initializePath($path);

return;
}

$repository = $package->getRepository();
$repoConfig = null;
if ($repository instanceof VcsRepository) {
$repoConfig = $this->getRepoConfig($repository);
}
$this->perforce = Perforce::create($repoConfig, $url, $path, $this->process, $this->io);
}

private function getRepoConfig(VcsRepository $repository)
{
return $repository->getRepoConfig();
}




public function doUpdate(PackageInterface $initial, PackageInterface $target, $path, $url)
{
$this->doDownload($target, $path, $url);
}




public function getLocalChanges(PackageInterface $package, $path)
{
$this->io->writeError('Perforce driver does not check for local changes before overriding', true);

return;
}




protected function getCommitLogs($fromReference, $toReference, $path)
{
$commitLogs = $this->perforce->getCommitLogs($fromReference, $toReference);

return $commitLogs;
}

public function setPerforce($perforce)
{
$this->perforce = $perforce;
}




protected function hasMetadataRepository($path)
{
return true;
}
}
