<?php











namespace Composer\Util;

use Composer\Config;
use Composer\IO\IOInterface;





class Svn
{
const MAX_QTY_AUTH_TRIES = 5;




protected $credentials;




protected $hasAuth;




protected $io;




protected $url;




protected $cacheCredentials = true;




protected $process;




protected $qtyAuthTries = 0;




protected $config;







public function __construct($url, IOInterface $io, Config $config, ProcessExecutor $process = null)
{
$this->url = $url;
$this->io = $io;
$this->config = $config;
$this->process = $process ?: new ProcessExecutor;
}

public static function cleanEnv()
{

 putenv("DYLD_LIBRARY_PATH");
unset($_SERVER['DYLD_LIBRARY_PATH']);
}














public function execute($command, $url, $cwd = null, $path = null, $verbose = false)
{
$svnCommand = $this->getCommand($command, $url, $path);
$output = null;
$io = $this->io;
$handler = function ($type, $buffer) use (&$output, $io, $verbose) {
if ($type !== 'out') {
return;
}
if ('Redirecting to URL ' === substr($buffer, 0, 19)) {
return;
}
$output .= $buffer;
if ($verbose) {
$io->writeError($buffer, false);
}
};
$status = $this->process->execute($svnCommand, $handler, $cwd);
if (0 === $status) {
return $output;
}

$errorOutput = $this->process->getErrorOutput();
$fullOutput = implode("\n", array($output, $errorOutput));


 if (false === stripos($fullOutput, 'Could not authenticate to server:')
&& false === stripos($fullOutput, 'authorization failed')
&& false === stripos($fullOutput, 'svn: E170001:')
&& false === stripos($fullOutput, 'svn: E215004:')) {
throw new \RuntimeException($fullOutput);
}

if (!$this->hasAuth()) {
$this->doAuthDance();
}


 if ($this->qtyAuthTries++ < self::MAX_QTY_AUTH_TRIES) {

 return $this->execute($command, $url, $cwd, $path, $verbose);
}

throw new \RuntimeException(
'wrong credentials provided ('.$fullOutput.')'
);
}




public function setCacheCredentials($cacheCredentials)
{
$this->cacheCredentials = $cacheCredentials;
}







protected function doAuthDance()
{

 if (!$this->io->isInteractive()) {
throw new \RuntimeException(
'can not ask for authentication in non interactive mode'
);
}

$this->io->writeError("The Subversion server ({$this->url}) requested credentials:");

$this->hasAuth = true;
$this->credentials['username'] = $this->io->ask("Username: ");
$this->credentials['password'] = $this->io->askAndHideAnswer("Password: ");

$this->cacheCredentials = $this->io->askConfirmation("Should Subversion cache these credentials? (yes/no) ", true);

return $this;
}










protected function getCommand($cmd, $url, $path = null)
{
$cmd = sprintf('%s %s%s %s',
$cmd,
'--non-interactive ',
$this->getCredentialString(),
ProcessExecutor::escape($url)
);

if ($path) {
$cmd .= ' ' . ProcessExecutor::escape($path);
}

return $cmd;
}








protected function getCredentialString()
{
if (!$this->hasAuth()) {
return '';
}

return sprintf(
' %s--username %s --password %s ',
$this->getAuthCache(),
ProcessExecutor::escape($this->getUsername()),
ProcessExecutor::escape($this->getPassword())
);
}







protected function getPassword()
{
if ($this->credentials === null) {
throw new \LogicException("No svn auth detected.");
}

return isset($this->credentials['password']) ? $this->credentials['password'] : '';
}







protected function getUsername()
{
if ($this->credentials === null) {
throw new \LogicException("No svn auth detected.");
}

return $this->credentials['username'];
}






protected function hasAuth()
{
if (null !== $this->hasAuth) {
return $this->hasAuth;
}

if (false === $this->createAuthFromConfig()) {
$this->createAuthFromUrl();
}

return $this->hasAuth;
}






protected function getAuthCache()
{
return $this->cacheCredentials ? '' : '--no-auth-cache ';
}






private function createAuthFromConfig()
{
if (!$this->config->has('http-basic')) {
return $this->hasAuth = false;
}

$authConfig = $this->config->get('http-basic');

$host = parse_url($this->url, PHP_URL_HOST);
if (isset($authConfig[$host])) {
$this->credentials['username'] = $authConfig[$host]['username'];
$this->credentials['password'] = $authConfig[$host]['password'];

return $this->hasAuth = true;
}

return $this->hasAuth = false;
}






private function createAuthFromUrl()
{
$uri = parse_url($this->url);
if (empty($uri['user'])) {
return $this->hasAuth = false;
}

$this->credentials['username'] = $uri['user'];
if (!empty($uri['pass'])) {
$this->credentials['password'] = $uri['pass'];
}

return $this->hasAuth = true;
}
}
