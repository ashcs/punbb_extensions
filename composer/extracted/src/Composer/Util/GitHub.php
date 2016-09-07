<?php











namespace Composer\Util;

use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Config;
use Composer\Downloader\TransportException;




class GitHub
{
protected $io;
protected $config;
protected $process;
protected $remoteFilesystem;









public function __construct(IOInterface $io, Config $config, ProcessExecutor $process = null, RemoteFilesystem $remoteFilesystem = null)
{
$this->io = $io;
$this->config = $config;
$this->process = $process ?: new ProcessExecutor;
$this->remoteFilesystem = $remoteFilesystem ?: Factory::createRemoteFilesystem($this->io, $config);
}







public function authorizeOAuth($originUrl)
{
if (!in_array($originUrl, $this->config->get('github-domains'))) {
return false;
}


 if (0 === $this->process->execute('git config github.accesstoken', $output)) {
$this->io->setAuthentication($originUrl, trim($output), 'x-oauth-basic');

return true;
}

return false;
}










public function authorizeOAuthInteractively($originUrl, $message = null)
{
if ($message) {
$this->io->writeError($message);
}

$note = 'Composer';
if ($this->config->get('github-expose-hostname') === true && 0 === $this->process->execute('hostname', $output)) {
$note .= ' on ' . trim($output);
}
$note .= ' ' . date('Y-m-d Hi');

$url = 'https://'.$originUrl.'/settings/tokens/new?scopes=repo&description=' . str_replace('%20', '+', rawurlencode($note));
$this->io->writeError(sprintf('Head to %s', $url));
$this->io->writeError(sprintf('to retrieve a token. It will be stored in "%s" for future use by Composer.', $this->config->getAuthConfigSource()->getName()));

$token = trim($this->io->askAndHideAnswer('Token (hidden): '));

if (!$token) {
$this->io->writeError('<warning>No token given, aborting.</warning>');
$this->io->writeError('You can also add it manually later by using "composer config github-oauth.github.com <token>"');

return false;
}

$this->io->setAuthentication($originUrl, $token, 'x-oauth-basic');

try {
$apiUrl = ('github.com' === $originUrl) ? 'api.github.com' : $originUrl . '/api/v3';

$this->remoteFilesystem->getContents($originUrl, 'https://'. $apiUrl . '/rate_limit', false, array(
'retry-auth-failure' => false,
));
} catch (TransportException $e) {
if (in_array($e->getCode(), array(403, 401))) {
$this->io->writeError('<error>Invalid token provided.</error>');
$this->io->writeError('You can also add it manually later by using "composer config github-oauth.github.com <token>"');

return false;
}

throw $e;
}


 $this->config->getConfigSource()->removeConfigSetting('github-oauth.'.$originUrl);
$this->config->getAuthConfigSource()->addConfigSetting('github-oauth.'.$originUrl, $token);

$this->io->writeError('<info>Token stored successfully.</info>');

return true;
}
}
