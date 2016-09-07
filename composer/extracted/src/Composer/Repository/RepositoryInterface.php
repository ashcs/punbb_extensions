<?php











namespace Composer\Repository;

use Composer\Package\PackageInterface;








interface RepositoryInterface extends \Countable
{
const SEARCH_FULLTEXT = 0;
const SEARCH_NAME = 1;








public function hasPackage(PackageInterface $package);









public function findPackage($name, $constraint);









public function findPackages($name, $constraint = null);






public function getPackages();








public function search($query, $mode = 0);
}
