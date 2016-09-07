<?php











namespace Composer\Package\Version;

use Composer\Config;
use Composer\Repository\Vcs\HgDriver;
use Composer\IO\NullIO;
use Composer\Semver\VersionParser as SemverVersionParser;
use Composer\Util\Git as GitUtil;
use Composer\Util\ProcessExecutor;
use Composer\Util\Svn as SvnUtil;







class VersionGuesser
{



private $config;




private $process;




private $versionParser;






public function __construct(Config $config, ProcessExecutor $process, SemverVersionParser $versionParser)
{
$this->config = $config;
$this->process = $process;
$this->versionParser = $versionParser;
}





public function guessVersion(array $packageConfig, $path)
{
if (function_exists('proc_open')) {
$version = $this->guessGitVersion($packageConfig, $path);
if (null !== $version) {
return $version;
}

$version = $this->guessHgVersion($packageConfig, $path);
if (null !== $version) {
return $version;
}

return $this->guessSvnVersion($packageConfig, $path);
}
}

private function guessGitVersion(array $packageConfig, $path)
{
GitUtil::cleanEnv();


 if (0 === $this->process->execute('git describe --exact-match --tags', $output, $path)) {
try {
return $this->versionParser->normalize(trim($output));
} catch (\Exception $e) {
}
}


 if (0 === $this->process->execute('git branch --no-color --no-abbrev -v', $output, $path)) {
$branches = array();
$isFeatureBranch = false;
$version = null;


 foreach ($this->process->splitLines($output) as $branch) {
if ($branch && preg_match('{^(?:\* ) *(\(no branch\)|\(detached from \S+\)|\S+) *([a-f0-9]+) .*$}', $branch, $match)) {
if ($match[1] === '(no branch)' || substr($match[1], 0, 10) === '(detached ') {
$version = 'dev-'.$match[2];
$isFeatureBranch = true;
} else {
$version = $this->versionParser->normalizeBranch($match[1]);
$isFeatureBranch = 0 === strpos($version, 'dev-');
if ('9999999-dev' === $version) {
$version = 'dev-'.$match[1];
}
}
}

if ($branch && !preg_match('{^ *[^/]+/HEAD }', $branch)) {
if (preg_match('{^(?:\* )? *(\S+) *([a-f0-9]+) .*$}', $branch, $match)) {
$branches[] = $match[1];
}
}
}

if (!$isFeatureBranch) {
return $version;
}


 $version = $this->guessFeatureVersion($packageConfig, $version, $branches, 'git rev-list %candidate%..%branch%', $path);

return $version;
}
}

private function guessHgVersion(array $packageConfig, $path)
{

 if (0 === $this->process->execute('hg branch', $output, $path)) {
$branch = trim($output);
$version = $this->versionParser->normalizeBranch($branch);
$isFeatureBranch = 0 === strpos($version, 'dev-');

if ('9999999-dev' === $version) {
$version = 'dev-'.$branch;
}

if (!$isFeatureBranch) {
return $version;
}


 $driver = new HgDriver(array('url' => $path), new NullIO(), $this->config, $this->process);
$branches = array_keys($driver->getBranches());


 $version = $this->guessFeatureVersion($packageConfig, $version, $branches, 'hg log -r "not ancestors(\'%candidate%\') and ancestors(\'%branch%\')" --template "{node}\\n"', $path);

return $version;
}
}

private function guessFeatureVersion(array $packageConfig, $version, array $branches, $scmCmdline, $path)
{

 
 if ((isset($packageConfig['extra']['branch-alias']) && !isset($packageConfig['extra']['branch-alias'][$version]))
|| strpos(json_encode($packageConfig), '"self.version"')
) {
$branch = preg_replace('{^dev-}', '', $version);
$length = PHP_INT_MAX;

$nonFeatureBranches = '';
if (!empty($packageConfig['non-feature-branches'])) {
$nonFeatureBranches = implode('|', $packageConfig['non-feature-branches']);
}

foreach ($branches as $candidate) {

 if ($candidate === $branch && preg_match('{^(' . $nonFeatureBranches . ')$}', $candidate)) {
return $version;
}


 if ($candidate === $branch || !preg_match('{^(master|trunk|default|develop|\d+\..+)$}', $candidate, $match)) {
continue;
}

$cmdLine = str_replace(array('%candidate%', '%branch%'), array($candidate, $branch), $scmCmdline);
if (0 !== $this->process->execute($cmdLine, $output, $path)) {
continue;
}

if (strlen($output) < $length) {
$length = strlen($output);
$version = $this->versionParser->normalizeBranch($candidate);
if ('9999999-dev' === $version) {
$version = 'dev-'.$match[1];
}
}
}
}

return $version;
}

private function guessSvnVersion(array $packageConfig, $path)
{
SvnUtil::cleanEnv();


 if (0 === $this->process->execute('svn info --xml', $output, $path)) {
$trunkPath = isset($packageConfig['trunk-path']) ? preg_quote($packageConfig['trunk-path'], '#') : 'trunk';
$branchesPath = isset($packageConfig['branches-path']) ? preg_quote($packageConfig['branches-path'], '#') : 'branches';
$tagsPath = isset($packageConfig['tags-path']) ? preg_quote($packageConfig['tags-path'], '#') : 'tags';

$urlPattern = '#<url>.*/('.$trunkPath.'|('.$branchesPath.'|'. $tagsPath .')/(.*))</url>#';

if (preg_match($urlPattern, $output, $matches)) {
if (isset($matches[2]) && ($branchesPath === $matches[2] || $tagsPath === $matches[2])) {

 $version = $this->versionParser->normalizeBranch($matches[3]);
if ('9999999-dev' === $version) {
$version = 'dev-'.$matches[3];
}

return $version;
}

return $this->versionParser->normalize(trim($matches[1]));
}
}
}
}
