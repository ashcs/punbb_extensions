<?php











namespace Composer\Package;






interface RootPackageInterface extends CompletePackageInterface
{





public function getAliases();






public function getMinimumStability();








public function getStabilityFlags();








public function getReferences();






public function getPreferStable();






public function setRequires(array $requires);






public function setDevRequires(array $devRequires);






public function setConflicts(array $conflicts);






public function setProvides(array $provides);






public function setReplaces(array $replaces);






public function setRepositories($repositories);






public function setAutoload(array $autoload);






public function setDevAutoload(array $devAutoload);






public function setStabilityFlags(array $stabilityFlags);






public function setSuggests(array $suggests);




public function setExtra(array $extra);
}
