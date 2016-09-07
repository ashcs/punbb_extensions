<?php











namespace Composer\Util;

use Composer\Composer;







final class StreamContextFactory
{









public static function getContext($url, array $defaultOptions = array(), array $defaultParams = array())
{
$options = array('http' => array(

 'follow_location' => 1,
'max_redirects' => 20,
));


 if (!empty($_SERVER['HTTP_PROXY']) || !empty($_SERVER['http_proxy'])) {

 $proxy = parse_url(!empty($_SERVER['http_proxy']) ? $_SERVER['http_proxy'] : $_SERVER['HTTP_PROXY']);
}


 if (preg_match('{^https://}i', $url) && (!empty($_SERVER['HTTPS_PROXY']) || !empty($_SERVER['https_proxy']))) {
$proxy = parse_url(!empty($_SERVER['https_proxy']) ? $_SERVER['https_proxy'] : $_SERVER['HTTPS_PROXY']);
}


 if (!empty($_SERVER['no_proxy']) && parse_url($url, PHP_URL_HOST)) {
$pattern = new NoProxyPattern($_SERVER['no_proxy']);
if ($pattern->test($url)) {
unset($proxy);
}
}

if (!empty($proxy)) {
$proxyURL = isset($proxy['scheme']) ? $proxy['scheme'] . '://' : '';
$proxyURL .= isset($proxy['host']) ? $proxy['host'] : '';

if (isset($proxy['port'])) {
$proxyURL .= ":" . $proxy['port'];
} elseif ('http://' == substr($proxyURL, 0, 7)) {
$proxyURL .= ":80";
} elseif ('https://' == substr($proxyURL, 0, 8)) {
$proxyURL .= ":443";
}


 $proxyURL = str_replace(array('http://', 'https://'), array('tcp://', 'ssl://'), $proxyURL);

if (0 === strpos($proxyURL, 'ssl:') && !extension_loaded('openssl')) {
throw new \RuntimeException('You must enable the openssl extension to use a proxy over https');
}

$options['http']['proxy'] = $proxyURL;


 switch (parse_url($url, PHP_URL_SCHEME)) {
case 'http': 
 $reqFullUriEnv = getenv('HTTP_PROXY_REQUEST_FULLURI');
if ($reqFullUriEnv === false || $reqFullUriEnv === '' || (strtolower($reqFullUriEnv) !== 'false' && (bool) $reqFullUriEnv)) {
$options['http']['request_fulluri'] = true;
}
break;
case 'https': 
 $reqFullUriEnv = getenv('HTTPS_PROXY_REQUEST_FULLURI');
if ($reqFullUriEnv === false || $reqFullUriEnv === '' || (strtolower($reqFullUriEnv) !== 'false' && (bool) $reqFullUriEnv)) {
$options['http']['request_fulluri'] = true;
}
break;
}


 if ('https' === parse_url($url, PHP_URL_SCHEME)) {
$options['ssl']['SNI_enabled'] = true;
if (PHP_VERSION_ID < 50600) {
$options['ssl']['SNI_server_name'] = parse_url($url, PHP_URL_HOST);
}
}


 if (isset($proxy['user'])) {
$auth = urldecode($proxy['user']);
if (isset($proxy['pass'])) {
$auth .= ':' . urldecode($proxy['pass']);
}
$auth = base64_encode($auth);


 if (isset($defaultOptions['http']['header'])) {
if (is_string($defaultOptions['http']['header'])) {
$defaultOptions['http']['header'] = array($defaultOptions['http']['header']);
}
$defaultOptions['http']['header'][] = "Proxy-Authorization: Basic {$auth}";
} else {
$options['http']['header'] = array("Proxy-Authorization: Basic {$auth}");
}
}
}

$options = array_replace_recursive($options, $defaultOptions);

if (isset($options['http']['header'])) {
$options['http']['header'] = self::fixHttpHeaderField($options['http']['header']);
}

if (defined('HHVM_VERSION')) {
$phpVersion = 'HHVM ' . HHVM_VERSION;
} else {
$phpVersion = 'PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
}

if (!isset($options['http']['header']) || false === strpos(strtolower(implode('', $options['http']['header'])), 'user-agent')) {
$options['http']['header'][] = sprintf(
'User-Agent: Composer/%s (%s; %s; %s)',
Composer::VERSION === '@package_version@' ? 'source' : Composer::VERSION,
php_uname('s'),
php_uname('r'),
$phpVersion
);
}

return stream_context_create($options, $defaultParams);
}











private static function fixHttpHeaderField($header)
{
if (!is_array($header)) {
$header = explode("\r\n", $header);
}
uasort($header, function ($el) {
return preg_match('{^content-type}i', $el) ? 1 : -1;
});

return $header;
}
}
