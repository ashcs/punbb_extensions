<?php











namespace Composer\Util;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Downloader\TransportException;






class RemoteFilesystem
{
private $io;
private $config;
private $bytesMax;
private $originUrl;
private $fileUrl;
private $fileName;
private $retry;
private $progress;
private $lastProgress;
private $options = array();
private $peerCertificateMap = array();
private $disableTls = false;
private $retryAuthFailure;
private $lastHeaders;
private $storeAuth;
private $degradedMode = false;
private $redirects;
private $maxRedirects = 20;









public function __construct(IOInterface $io, Config $config = null, array $options = array(), $disableTls = false)
{
$this->io = $io;


 
 if ($disableTls === false) {
$this->options = $this->getTlsDefaults($options);
} else {
$this->disableTls = true;
}


 $this->options = array_replace_recursive($this->options, $options);
$this->config = $config;
}












public function copy($originUrl, $fileUrl, $fileName, $progress = true, $options = array())
{
return $this->get($originUrl, $fileUrl, $options, $fileName, $progress);
}











public function getContents($originUrl, $fileUrl, $progress = true, $options = array())
{
return $this->get($originUrl, $fileUrl, $options, null, $progress);
}






public function getOptions()
{
return $this->options;
}






public function setOptions(array $options)
{
$this->options = array_replace_recursive($this->options, $options);
}

public function isTlsDisabled()
{
return $this->disableTls === true;
}






public function getLastHeaders()
{
return $this->lastHeaders;
}






public function findHeaderValue(array $headers, $name)
{
$value = null;
foreach ($headers as $header) {
if (preg_match('{^'.$name.':\s*(.+?)\s*$}i', $header, $match)) {
$value = $match[1];
} elseif (preg_match('{^HTTP/}i', $header)) {

 
 $value = null;
}
}

return $value;
}





public function findStatusCode(array $headers)
{
$value = null;
foreach ($headers as $header) {
if (preg_match('{^HTTP/\S+ (\d+)}i', $header, $match)) {

 
 $value = (int) $match[1];
}
}

return $value;
}















protected function get($originUrl, $fileUrl, $additionalOptions = array(), $fileName = null, $progress = true)
{
if (strpos($originUrl, '.github.com') === (strlen($originUrl) - 11)) {
$originUrl = 'github.com';
}

$this->scheme = parse_url($fileUrl, PHP_URL_SCHEME);
$this->bytesMax = 0;
$this->originUrl = $originUrl;
$this->fileUrl = $fileUrl;
$this->fileName = $fileName;
$this->progress = $progress;
$this->lastProgress = null;
$this->retryAuthFailure = true;
$this->lastHeaders = array();
$this->redirects = 1; 


 if (preg_match('{^https?://(.+):(.+)@([^/]+)}i', $fileUrl, $match)) {
$this->io->setAuthentication($originUrl, urldecode($match[1]), urldecode($match[2]));
}

$tempAdditionalOptions = $additionalOptions;
if (isset($tempAdditionalOptions['retry-auth-failure'])) {
$this->retryAuthFailure = (bool) $tempAdditionalOptions['retry-auth-failure'];

unset($tempAdditionalOptions['retry-auth-failure']);
}

$isRedirect = false;
if (isset($tempAdditionalOptions['redirects'])) {
$this->redirects = $tempAdditionalOptions['redirects'];
$isRedirect = true;

unset($tempAdditionalOptions['redirects']);
}

$options = $this->getOptionsForUrl($originUrl, $tempAdditionalOptions);
unset($tempAdditionalOptions);
$userlandFollow = isset($options['http']['follow_location']) && !$options['http']['follow_location'];

$this->io->writeError((substr($fileUrl, 0, 4) === 'http' ? 'Downloading ' : 'Reading ') . $fileUrl, true, IOInterface::DEBUG);

if (isset($options['github-token'])) {
$fileUrl .= (false === strpos($fileUrl, '?') ? '?' : '&') . 'access_token='.$options['github-token'];
unset($options['github-token']);
}

if (isset($options['gitlab-token'])) {
$fileUrl .= (false === strpos($fileUrl, '?') ? '?' : '&') . 'access_token='.$options['gitlab-token'];
unset($options['gitlab-token']);
}

if (isset($options['http'])) {
$options['http']['ignore_errors'] = true;
}

if ($this->degradedMode && substr($fileUrl, 0, 21) === 'http://packagist.org/') {

 $fileUrl = 'http://' . gethostbyname('packagist.org') . substr($fileUrl, 20);
}

$ctx = StreamContextFactory::getContext($fileUrl, $options, array('notification' => array($this, 'callbackGet')));

if ($this->progress && !$isRedirect) {
$this->io->writeError("    Downloading: <comment>Connecting...</comment>", false);
}

$errorMessage = '';
$errorCode = 0;
$result = false;
set_error_handler(function ($code, $msg) use (&$errorMessage) {
if ($errorMessage) {
$errorMessage .= "\n";
}
$errorMessage .= preg_replace('{^file_get_contents\(.*?\): }', '', $msg);
});
try {
$result = file_get_contents($fileUrl, false, $ctx);

if (PHP_VERSION_ID < 50600 && !empty($options['ssl']['peer_fingerprint'])) {

 $params = stream_context_get_params($ctx);
$expectedPeerFingerprint = $options['ssl']['peer_fingerprint'];
$peerFingerprint = TlsHelper::getCertificateFingerprint($params['options']['ssl']['peer_certificate']);


 if ($expectedPeerFingerprint !== $peerFingerprint) {
throw new TransportException('Peer fingerprint did not match');
}
}
} catch (\Exception $e) {
if ($e instanceof TransportException && !empty($http_response_header[0])) {
$e->setHeaders($http_response_header);
$e->setStatusCode($this->findStatusCode($http_response_header));
}
if ($e instanceof TransportException && $result !== false) {
$e->setResponse($result);
}
$result = false;
}
if ($errorMessage && !ini_get('allow_url_fopen')) {
$errorMessage = 'allow_url_fopen must be enabled in php.ini ('.$errorMessage.')';
}
restore_error_handler();
if (isset($e) && !$this->retry) {
if (!$this->degradedMode && false !== strpos($e->getMessage(), 'Operation timed out')) {
$this->degradedMode = true;
$this->io->writeError(array(
'<error>'.$e->getMessage().'</error>',
'<error>Retrying with degraded mode, check https://getcomposer.org/doc/articles/troubleshooting.md#degraded-mode for more info</error>',
));

return $this->get($this->originUrl, $this->fileUrl, $additionalOptions, $this->fileName, $this->progress);
}

throw $e;
}

$statusCode = null;
if (!empty($http_response_header[0])) {
$statusCode = $this->findStatusCode($http_response_header);
}


 $hasFollowedRedirect = false;
if ($userlandFollow && $statusCode >= 300 && $statusCode <= 399 && $statusCode !== 304 && $this->redirects < $this->maxRedirects) {
$hasFollowedRedirect = true;
$result = $this->handleRedirect($http_response_header, $additionalOptions, $result);
}


 if ($statusCode && $statusCode >= 400 && $statusCode <= 599) {
if (!$this->retry) {
$e = new TransportException('The "'.$this->fileUrl.'" file could not be downloaded ('.$http_response_header[0].')', $statusCode);
$e->setHeaders($http_response_header);
$e->setResponse($result);
$e->setStatusCode($statusCode);
throw $e;
}
$result = false;
}

if ($this->progress && !$this->retry && !$isRedirect) {
$this->io->overwriteError("    Downloading: <comment>100%</comment>");
}


 if ($result && extension_loaded('zlib') && substr($fileUrl, 0, 4) === 'http' && !$hasFollowedRedirect) {
$decode = 'gzip' === strtolower($this->findHeaderValue($http_response_header, 'content-encoding'));

if ($decode) {
try {
if (PHP_VERSION_ID >= 50400) {
$result = zlib_decode($result);
} else {

 $result = file_get_contents('compress.zlib://data:application/octet-stream;base64,'.base64_encode($result));
}

if (!$result) {
throw new TransportException('Failed to decode zlib stream');
}
} catch (\Exception $e) {
if ($this->degradedMode) {
throw $e;
}

$this->degradedMode = true;
$this->io->writeError(array(
'<error>Failed to decode response: '.$e->getMessage().'</error>',
'<error>Retrying with degraded mode, check https://getcomposer.org/doc/articles/troubleshooting.md#degraded-mode for more info</error>',
));

return $this->get($this->originUrl, $this->fileUrl, $additionalOptions, $this->fileName, $this->progress);
}
}
}


 if (false !== $result && null !== $fileName && !$isRedirect) {
if ('' === $result) {
throw new TransportException('"'.$this->fileUrl.'" appears broken, and returned an empty 200 response');
}

$errorMessage = '';
set_error_handler(function ($code, $msg) use (&$errorMessage) {
if ($errorMessage) {
$errorMessage .= "\n";
}
$errorMessage .= preg_replace('{^file_put_contents\(.*?\): }', '', $msg);
});
$result = (bool) file_put_contents($fileName, $result);
restore_error_handler();
if (false === $result) {
throw new TransportException('The "'.$this->fileUrl.'" file could not be written to '.$fileName.': '.$errorMessage);
}
}


 if (false === $result && false !== strpos($errorMessage, 'Peer certificate') && PHP_VERSION_ID < 50600) {

 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 if (TlsHelper::isOpensslParseSafe()) {
$certDetails = $this->getCertificateCnAndFp($this->fileUrl, $options);

if ($certDetails) {
$this->peerCertificateMap[$this->getUrlAuthority($this->fileUrl)] = $certDetails;

$this->retry = true;
}
} else {
$this->io->writeError(sprintf(
'<error>Your version of PHP, %s, is affected by CVE-2013-6420 and cannot safely perform certificate validation, we strongly suggest you upgrade.</error>',
PHP_VERSION
));
}
}

if ($this->retry) {
$this->retry = false;

$result = $this->get($this->originUrl, $this->fileUrl, $additionalOptions, $this->fileName, $this->progress);

if ($this->storeAuth && $this->config) {
$authHelper = new AuthHelper($this->io, $this->config);
$authHelper->storeAuth($this->originUrl, $this->storeAuth);
$this->storeAuth = false;
}

return $result;
}

if (false === $result) {
$e = new TransportException('The "'.$this->fileUrl.'" file could not be downloaded: '.$errorMessage, $errorCode);
if (!empty($http_response_header[0])) {
$e->setHeaders($http_response_header);
}

if (!$this->degradedMode && false !== strpos($e->getMessage(), 'Operation timed out')) {
$this->degradedMode = true;
$this->io->writeError(array(
'<error>'.$e->getMessage().'</error>',
'<error>Retrying with degraded mode, check https://getcomposer.org/doc/articles/troubleshooting.md#degraded-mode for more info</error>',
));

return $this->get($this->originUrl, $this->fileUrl, $additionalOptions, $this->fileName, $this->progress);
}

throw $e;
}

if (!empty($http_response_header[0])) {
$this->lastHeaders = $http_response_header;
}

return $result;
}












protected function callbackGet($notificationCode, $severity, $message, $messageCode, $bytesTransferred, $bytesMax)
{
switch ($notificationCode) {
case STREAM_NOTIFY_FAILURE:
if (400 === $messageCode) {

 
 throw new TransportException("The '" . $this->fileUrl . "' URL could not be accessed: " . $message, $messageCode);
}

 

case STREAM_NOTIFY_AUTH_REQUIRED:
if (401 === $messageCode) {

 if (!$this->retryAuthFailure) {
break;
}

$this->promptAuthAndRetry($messageCode);
}
break;

case STREAM_NOTIFY_AUTH_RESULT:
if (403 === $messageCode) {

 if (!$this->retryAuthFailure) {
break;
}

$this->promptAuthAndRetry($messageCode, $message);
}
break;

case STREAM_NOTIFY_FILE_SIZE_IS:
if ($this->bytesMax < $bytesMax) {
$this->bytesMax = $bytesMax;
}
break;

case STREAM_NOTIFY_PROGRESS:
if ($this->bytesMax > 0 && $this->progress) {
$progression = round($bytesTransferred / $this->bytesMax * 100);

if ((0 === $progression % 5) && 100 !== $progression && $progression !== $this->lastProgress) {
$this->lastProgress = $progression;
$this->io->overwriteError("    Downloading: <comment>$progression%</comment>", false);
}
}
break;

default:
break;
}
}

protected function promptAuthAndRetry($httpStatus, $reason = null)
{
if ($this->config && in_array($this->originUrl, $this->config->get('github-domains'), true)) {
$message = "\n".'Could not fetch '.$this->fileUrl.', please create a GitHub OAuth token '.($httpStatus === 404 ? 'to access private repos' : 'to go over the API rate limit');
$gitHubUtil = new GitHub($this->io, $this->config, null);
if (!$gitHubUtil->authorizeOAuth($this->originUrl)
&& (!$this->io->isInteractive() || !$gitHubUtil->authorizeOAuthInteractively($this->originUrl, $message))
) {
throw new TransportException('Could not authenticate against '.$this->originUrl, 401);
}
} elseif ($this->config && in_array($this->originUrl, $this->config->get('gitlab-domains'), true)) {
$message = "\n".'Could not fetch '.$this->fileUrl.', enter your ' . $this->originUrl . ' credentials ' .($httpStatus === 401 ? 'to access private repos' : 'to go over the API rate limit');
$gitLabUtil = new GitLab($this->io, $this->config, null);
if (!$gitLabUtil->authorizeOAuth($this->originUrl)
&& (!$this->io->isInteractive() || !$gitLabUtil->authorizeOAuthInteractively($this->scheme, $this->originUrl, $message))
) {
throw new TransportException('Could not authenticate against '.$this->originUrl, 401);
}
} else {

 if ($httpStatus === 404) {
return;
}


 if (!$this->io->isInteractive()) {
if ($httpStatus === 401) {
$message = "The '" . $this->fileUrl . "' URL required authentication.\nYou must be using the interactive console to authenticate";
}
if ($httpStatus === 403) {
$message = "The '" . $this->fileUrl . "' URL could not be accessed: " . $reason;
}

throw new TransportException($message, $httpStatus);
}

 if ($this->io->hasAuthentication($this->originUrl)) {
throw new TransportException("Invalid credentials for '" . $this->fileUrl . "', aborting.", $httpStatus);
}

$this->io->overwriteError('    Authentication required (<info>'.parse_url($this->fileUrl, PHP_URL_HOST).'</info>):');
$username = $this->io->ask('      Username: ');
$password = $this->io->askAndHideAnswer('      Password: ');
$this->io->setAuthentication($this->originUrl, $username, $password);
$this->storeAuth = $this->config->get('store-auths');
}

$this->retry = true;
throw new TransportException('RETRY');
}

protected function getOptionsForUrl($originUrl, $additionalOptions)
{
$tlsOptions = array();


 if ($this->disableTls === false && PHP_VERSION_ID < 50600 && !stream_is_local($this->fileUrl)) {
$host = parse_url($this->fileUrl, PHP_URL_HOST);

if (PHP_VERSION_ID >= 50304) {

 
 $userlandFollow = true;
} else {

 
 
 

if ($host === 'github.com' || $host === 'api.github.com') {
$host = '*.github.com';
}
}

$tlsOptions['ssl']['CN_match'] = $host;
$tlsOptions['ssl']['SNI_server_name'] = $host;

$urlAuthority = $this->getUrlAuthority($this->fileUrl);

if (isset($this->peerCertificateMap[$urlAuthority])) {

 $certMap = $this->peerCertificateMap[$urlAuthority];

$this->io->writeError(sprintf(
'Using <info>%s</info> as CN for subjectAltName enabled host <info>%s</info>',
$certMap['cn'],
$urlAuthority
), true, IOInterface::DEBUG);

$tlsOptions['ssl']['CN_match'] = $certMap['cn'];
$tlsOptions['ssl']['peer_fingerprint'] = $certMap['fp'];
}
}

$headers = array();

if (extension_loaded('zlib')) {
$headers[] = 'Accept-Encoding: gzip';
}

$options = array_replace_recursive($this->options, $tlsOptions, $additionalOptions);
if (!$this->degradedMode) {

 
 $options['http']['protocol_version'] = 1.1;
$headers[] = 'Connection: close';
}

if (isset($userlandFollow)) {
$options['http']['follow_location'] = 0;
}

if ($this->io->hasAuthentication($originUrl)) {
$auth = $this->io->getAuthentication($originUrl);
if ('github.com' === $originUrl && 'x-oauth-basic' === $auth['password']) {
$options['github-token'] = $auth['username'];
} elseif ($this->config && in_array($originUrl, $this->config->get('gitlab-domains'), true)) {
if ($auth['password'] === 'oauth2') {
$headers[] = 'Authorization: Bearer '.$auth['username'];
}
} else {
$authStr = base64_encode($auth['username'] . ':' . $auth['password']);
$headers[] = 'Authorization: Basic '.$authStr;
}
}

if (isset($options['http']['header']) && !is_array($options['http']['header'])) {
$options['http']['header'] = explode("\r\n", trim($options['http']['header'], "\r\n"));
}
foreach ($headers as $header) {
$options['http']['header'][] = $header;
}

return $options;
}

private function handleRedirect(array $http_response_header, array $additionalOptions, $result)
{
if ($locationHeader = $this->findHeaderValue($http_response_header, 'location')) {
if (parse_url($locationHeader, PHP_URL_SCHEME)) {

 $targetUrl = $locationHeader;
} elseif (parse_url($locationHeader, PHP_URL_HOST)) {

 $targetUrl = $this->scheme.':'.$locationHeader;
} elseif ('/' === $locationHeader[0]) {

 $urlHost = parse_url($this->fileUrl, PHP_URL_HOST);


 $targetUrl = preg_replace('{^(.+(?://|@)'.preg_quote($urlHost).'(?::\d+)?)(?:[/\?].*)?$}', '\1'.$locationHeader, $this->fileUrl);
} else {

 
 $targetUrl = preg_replace('{^(.+/)[^/?]*(?:\?.*)?$}', '\1'.$locationHeader, $this->fileUrl);
}
}

if (!empty($targetUrl)) {
$this->redirects++;

$this->io->writeError(sprintf('Following redirect (%u) %s', $this->redirects, $targetUrl), true, IOInterface::DEBUG);

$additionalOptions['redirects'] = $this->redirects;

return $this->get($this->originUrl, $targetUrl, $additionalOptions, $this->fileName, $this->progress);
}

if (!$this->retry) {
$e = new TransportException('The "'.$this->fileUrl.'" file could not be downloaded, got redirect without Location ('.$http_response_header[0].')');
$e->setHeaders($http_response_header);
$e->setResponse($result);

throw $e;
}

return false;
}






private function getTlsDefaults(array $options)
{
$ciphers = implode(':', array(
'ECDHE-RSA-AES128-GCM-SHA256',
'ECDHE-ECDSA-AES128-GCM-SHA256',
'ECDHE-RSA-AES256-GCM-SHA384',
'ECDHE-ECDSA-AES256-GCM-SHA384',
'DHE-RSA-AES128-GCM-SHA256',
'DHE-DSS-AES128-GCM-SHA256',
'kEDH+AESGCM',
'ECDHE-RSA-AES128-SHA256',
'ECDHE-ECDSA-AES128-SHA256',
'ECDHE-RSA-AES128-SHA',
'ECDHE-ECDSA-AES128-SHA',
'ECDHE-RSA-AES256-SHA384',
'ECDHE-ECDSA-AES256-SHA384',
'ECDHE-RSA-AES256-SHA',
'ECDHE-ECDSA-AES256-SHA',
'DHE-RSA-AES128-SHA256',
'DHE-RSA-AES128-SHA',
'DHE-DSS-AES128-SHA256',
'DHE-RSA-AES256-SHA256',
'DHE-DSS-AES256-SHA',
'DHE-RSA-AES256-SHA',
'AES128-GCM-SHA256',
'AES256-GCM-SHA384',
'ECDHE-RSA-RC4-SHA',
'ECDHE-ECDSA-RC4-SHA',
'AES128',
'AES256',
'RC4-SHA',
'HIGH',
'!aNULL',
'!eNULL',
'!EXPORT',
'!DES',
'!3DES',
'!MD5',
'!PSK',
));







$defaults = array(
'ssl' => array(
'ciphers' => $ciphers,
'verify_peer' => true,
'verify_depth' => 7,
'SNI_enabled' => true,
'capture_peer_cert' => true,
),
);

if (isset($options['ssl'])) {
$defaults['ssl'] = array_replace_recursive($defaults['ssl'], $options['ssl']);
}





if (!isset($defaults['ssl']['cafile']) && !isset($defaults['ssl']['capath'])) {
$result = $this->getSystemCaRootBundlePath();

if (preg_match('{^phar://}', $result)) {
$hash = hash_file('sha256', $result);
$targetPath = rtrim(sys_get_temp_dir(), '\\/') . '/composer-cacert-' . $hash . '.pem';

if (!file_exists($targetPath) || $hash !== hash_file('sha256', $targetPath)) {
$this->streamCopy($result, $targetPath);
chmod($targetPath, 0666);
}

$defaults['ssl']['cafile'] = $targetPath;
} elseif (is_dir($result)) {
$defaults['ssl']['capath'] = $result;
} else {
$defaults['ssl']['cafile'] = $result;
}
}

if (isset($defaults['ssl']['cafile']) && (!is_readable($defaults['ssl']['cafile']) || !$this->validateCaFile($defaults['ssl']['cafile']))) {
throw new TransportException('The configured cafile was not valid or could not be read.');
}

if (isset($defaults['ssl']['capath']) && (!is_dir($defaults['ssl']['capath']) || !is_readable($defaults['ssl']['capath']))) {
throw new TransportException('The configured capath was not valid or could not be read.');
}




if (PHP_VERSION_ID >= 50413) {
$defaults['ssl']['disable_compression'] = true;
}

return $defaults;
}



































private function getSystemCaRootBundlePath()
{
static $caPath = null;

if ($caPath !== null) {
return $caPath;
}


 
 $envCertFile = getenv('SSL_CERT_FILE');
if ($envCertFile && is_readable($envCertFile) && $this->validateCaFile($envCertFile)) {

 return $caPath = $envCertFile;
}

$configured = ini_get('openssl.cafile');
if ($configured && strlen($configured) > 0 && is_readable($configured) && $this->validateCaFile($configured)) {
return $caPath = $configured;
}

$caBundlePaths = array(
'/etc/pki/tls/certs/ca-bundle.crt', 
 '/etc/ssl/certs/ca-certificates.crt', 
 '/etc/ssl/ca-bundle.pem', 
 '/usr/local/share/certs/ca-root-nss.crt', 
 '/usr/ssl/certs/ca-bundle.crt', 
 '/opt/local/share/curl/curl-ca-bundle.crt', 
 '/usr/local/share/curl/curl-ca-bundle.crt', 
 '/usr/share/ssl/certs/ca-bundle.crt', 
 '/etc/ssl/cert.pem', 
 '/usr/local/etc/ssl/cert.pem', 
 );

foreach ($caBundlePaths as $caBundle) {
if (Silencer::call('is_readable', $caBundle) && $this->validateCaFile($caBundle)) {
return $caPath = $caBundle;
}
}

foreach ($caBundlePaths as $caBundle) {
$caBundle = dirname($caBundle);
if (is_dir($caBundle) && glob($caBundle.'/*')) {
return $caPath = $caBundle;
}
}

return $caPath = __DIR__.'/../../../res/cacert.pem'; 
 }






private function validateCaFile($filename)
{
static $files = array();

if (isset($files[$filename])) {
return $files[$filename];
}

$this->io->writeError('Checking CA file '.realpath($filename), true, IOInterface::DEBUG);
$contents = file_get_contents($filename);


 
 if (!TlsHelper::isOpensslParseSafe()) {
$this->io->writeError(sprintf(
'<error>Your version of PHP, %s, is affected by CVE-2013-6420 and cannot safely perform certificate validation, we strongly suggest you upgrade.</error>',
PHP_VERSION
));

return $files[$filename] = !empty($contents);
}

return $files[$filename] = (bool) openssl_x509_parse($contents);
}







private function streamCopy($source, $target)
{
$source = fopen($source, 'r');
$target = fopen($target, 'w+');

stream_copy_to_stream($source, $target);
fclose($source);
fclose($target);

unset($source, $target);
}






private function getCertificateCnAndFp($url, $options)
{
if (PHP_VERSION_ID >= 50600) {
throw new \BadMethodCallException(sprintf(
'%s must not be used on PHP >= 5.6',
__METHOD__
));
}

$context = StreamContextFactory::getContext($url, $options, array('options' => array(
'ssl' => array(
'capture_peer_cert' => true,
'verify_peer' => false, 
 ), ),
));


 
 if (false === $handle = @fopen($url, 'rb', false, $context)) {
return;
}


 fclose($handle);
$handle = null;

$params = stream_context_get_params($context);

if (!empty($params['options']['ssl']['peer_certificate'])) {
$peerCertificate = $params['options']['ssl']['peer_certificate'];

if (TlsHelper::checkCertificateHost($peerCertificate, parse_url($url, PHP_URL_HOST), $commonName)) {
return array(
'cn' => $commonName,
'fp' => TlsHelper::getCertificateFingerprint($peerCertificate),
);
}
}
}

private function getUrlAuthority($url)
{
$defaultPorts = array(
'ftp' => 21,
'http' => 80,
'https' => 443,
'ssh2.sftp' => 22,
'ssh2.scp' => 22,
);

$scheme = parse_url($url, PHP_URL_SCHEME);

if (!isset($defaultPorts[$scheme])) {
throw new \InvalidArgumentException(sprintf(
'Could not get default port for unknown scheme: %s',
$scheme
));
}

$defaultPort = $defaultPorts[$scheme];
$port = parse_url($url, PHP_URL_PORT) ?: $defaultPort;

return parse_url($url, PHP_URL_HOST).':'.$port;
}
}
