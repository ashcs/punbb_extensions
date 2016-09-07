<?php











namespace Composer\Util;

use Composer\IO\IOInterface;
use Composer\Config;
use Composer\Factory;
use Composer\Downloader\TransportException;
use Composer\Json\JsonFile;




class GitLab
{
protected $io;
protected $config;
protected $process;
protected $remoteFilesystem;









public function __construct(IOInterface $io, Config $config, ProcessExecutor $process = null, RemoteFilesystem $remoteFilesystem = null)
{
$this->io = $io;
$this->config = $config;
$this->process = $process ?: new ProcessExecutor();
$this->remoteFilesystem = $remoteFilesystem ?: Factory::createRemoteFilesystem($this->io, $config);
}








public function authorizeOAuth($originUrl)
{
if (!in_array($originUrl, $this->config->get('gitlab-domains'), true)) {
return false;
}


 if (0 === $this->process->execute('git config gitlab.accesstoken', $output)) {
$this->io->setAuthentication($originUrl, trim($output), 'oauth2');

return true;
}

return false;
}












public function authorizeOAuthInteractively($scheme, $originUrl, $message = null)
{
if ($message) {
$this->io->writeError($message);
}

$this->io->writeError(sprintf('A token will be created and stored in "%s", your password will never be stored', $this->config->getAuthConfigSource()->getName()));
$this->io->writeError('To revoke access to this token you can visit '.$originUrl.'/profile/applications');

$attemptCounter = 0;

while ($attemptCounter++ < 5) {
try {
$response = $this->createToken($scheme, $originUrl);
} catch (TransportException $e) {

 
 if (in_array($e->getCode(), array(403, 401))) {
if (401 === $e->getCode()) {
$this->io->writeError('Bad credentials.');
} else {
$this->io->writeError('Maximum number of login attempts exceeded. Please try again later.');
}

$this->io->writeError('You can also manually create a personal token at '.$scheme.'://'.$originUrl.'/profile/applications');
$this->io->writeError('Add it using "composer config gitlab-oauth.'.$originUrl.' <token>"');

continue;
}

throw $e;
}

$this->io->setAuthentication($originUrl, $response['access_token'], 'oauth2');


 $this->config->getAuthConfigSource()->addConfigSetting('gitlab-oauth.'.$originUrl, $response['access_token']);

return true;
}

throw new \RuntimeException('Invalid GitLab credentials 5 times in a row, aborting.');
}

private function createToken($scheme, $originUrl)
{
$username = $this->io->ask('Username: ');
$password = $this->io->askAndHideAnswer('Password: ');

$headers = array('Content-Type: application/x-www-form-urlencoded');

$apiUrl = $originUrl;
$data = http_build_query(array(
'username' => $username,
'password' => $password,
'grant_type' => 'password',
));
$options = array(
'retry-auth-failure' => false,
'http' => array(
'method' => 'POST',
'header' => $headers,
'content' => $data,
),
);

$json = $this->remoteFilesystem->getContents($originUrl, $scheme.'://'.$apiUrl.'/oauth/token', false, $options);

$this->io->writeError('Token successfully created');

return JsonFile::parseJson($json);
}
}
