<?php











namespace Composer\Package;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;




class AliasPackage extends BasePackage implements CompletePackageInterface
{
protected $version;
protected $prettyVersion;
protected $dev;
protected $aliasOf;
protected $rootPackageAlias = false;
protected $stability;


protected $requires;

protected $devRequires;

protected $conflicts;

protected $provides;

protected $replaces;








public function __construct(PackageInterface $aliasOf, $version, $prettyVersion)
{
parent::__construct($aliasOf->getName());

$this->version = $version;
$this->prettyVersion = $prettyVersion;
$this->aliasOf = $aliasOf;
$this->stability = VersionParser::parseStability($version);
$this->dev = $this->stability === 'dev';

foreach (array('requires', 'devRequires', 'conflicts', 'provides', 'replaces') as $type) {
$links = $aliasOf->{'get' . ucfirst($type)}();
$this->$type = $this->replaceSelfVersionDependencies($links, $type);
}
}




public function getAliasOf()
{
return $this->aliasOf;
}




public function getVersion()
{
return $this->version;
}




public function getStability()
{
return $this->stability;
}




public function getPrettyVersion()
{
return $this->prettyVersion;
}




public function isDev()
{
return $this->dev;
}




public function getRequires()
{
return $this->requires;
}




public function getConflicts()
{
return $this->conflicts;
}




public function getProvides()
{
return $this->provides;
}




public function getReplaces()
{
return $this->replaces;
}




public function getDevRequires()
{
return $this->devRequires;
}










public function setRootPackageAlias($value)
{
return $this->rootPackageAlias = $value;
}





public function isRootPackageAlias()
{
return $this->rootPackageAlias;
}







protected function replaceSelfVersionDependencies(array $links, $linkType)
{
if (in_array($linkType, array('conflicts', 'provides', 'replaces'), true)) {
$newLinks = array();
foreach ($links as $link) {

 if ('self.version' === $link->getPrettyConstraint()) {
$newLinks[] = new Link($link->getSource(), $link->getTarget(), new Constraint('=', $this->version), $linkType, $this->prettyVersion);
}
}
$links = array_merge($links, $newLinks);
} else {
foreach ($links as $index => $link) {
if ('self.version' === $link->getPrettyConstraint()) {
$links[$index] = new Link($link->getSource(), $link->getTarget(), new Constraint('=', $this->version), $linkType, $this->prettyVersion);
}
}
}

return $links;
}





public function getType()
{
return $this->aliasOf->getType();
}

public function getTargetDir()
{
return $this->aliasOf->getTargetDir();
}

public function getExtra()
{
return $this->aliasOf->getExtra();
}

public function setInstallationSource($type)
{
$this->aliasOf->setInstallationSource($type);
}

public function getInstallationSource()
{
return $this->aliasOf->getInstallationSource();
}

public function getSourceType()
{
return $this->aliasOf->getSourceType();
}

public function getSourceUrl()
{
return $this->aliasOf->getSourceUrl();
}

public function getSourceUrls()
{
return $this->aliasOf->getSourceUrls();
}

public function getSourceReference()
{
return $this->aliasOf->getSourceReference();
}

public function setSourceReference($reference)
{
return $this->aliasOf->setSourceReference($reference);
}

public function setSourceMirrors($mirrors)
{
return $this->aliasOf->setSourceMirrors($mirrors);
}

public function getSourceMirrors()
{
return $this->aliasOf->getSourceMirrors();
}

public function getDistType()
{
return $this->aliasOf->getDistType();
}

public function getDistUrl()
{
return $this->aliasOf->getDistUrl();
}

public function getDistUrls()
{
return $this->aliasOf->getDistUrls();
}

public function getDistReference()
{
return $this->aliasOf->getDistReference();
}

public function setDistReference($reference)
{
return $this->aliasOf->setDistReference($reference);
}

public function getDistSha1Checksum()
{
return $this->aliasOf->getDistSha1Checksum();
}

public function setTransportOptions(array $options)
{
return $this->aliasOf->setTransportOptions($options);
}

public function getTransportOptions()
{
return $this->aliasOf->getTransportOptions();
}

public function setDistMirrors($mirrors)
{
return $this->aliasOf->setDistMirrors($mirrors);
}

public function getDistMirrors()
{
return $this->aliasOf->getDistMirrors();
}

public function getScripts()
{
return $this->aliasOf->getScripts();
}

public function getLicense()
{
return $this->aliasOf->getLicense();
}

public function getAutoload()
{
return $this->aliasOf->getAutoload();
}

public function getDevAutoload()
{
return $this->aliasOf->getDevAutoload();
}

public function getIncludePaths()
{
return $this->aliasOf->getIncludePaths();
}

public function getRepositories()
{
return $this->aliasOf->getRepositories();
}

public function getReleaseDate()
{
return $this->aliasOf->getReleaseDate();
}

public function getBinaries()
{
return $this->aliasOf->getBinaries();
}

public function getKeywords()
{
return $this->aliasOf->getKeywords();
}

public function getDescription()
{
return $this->aliasOf->getDescription();
}

public function getHomepage()
{
return $this->aliasOf->getHomepage();
}

public function getSuggests()
{
return $this->aliasOf->getSuggests();
}

public function getAuthors()
{
return $this->aliasOf->getAuthors();
}

public function getSupport()
{
return $this->aliasOf->getSupport();
}

public function getNotificationUrl()
{
return $this->aliasOf->getNotificationUrl();
}

public function getArchiveExcludes()
{
return $this->aliasOf->getArchiveExcludes();
}

public function isAbandoned()
{
return $this->aliasOf->isAbandoned();
}

public function getReplacementPackage()
{
return $this->aliasOf->getReplacementPackage();
}

public function __toString()
{
return parent::__toString().' (alias of '.$this->aliasOf->getVersion().')';
}
}
