<?php











namespace Composer\Util;

use Symfony\Component\Process\PhpProcess;




final class TlsHelper
{
private static $useOpensslParse;










public static function checkCertificateHost($certificate, $hostname, &$cn = null)
{
$names = self::getCertificateNames($certificate);

if (empty($names)) {
return false;
}

$combinedNames = array_merge($names['san'], array($names['cn']));
$hostname = strtolower($hostname);

foreach ($combinedNames as $certName) {
$matcher = self::certNameMatcher($certName);

if ($matcher && $matcher($hostname)) {
$cn = $names['cn'];

return true;
}
}

return false;
}








public static function getCertificateNames($certificate)
{
if (is_array($certificate)) {
$info = $certificate;
} elseif (self::isOpensslParseSafe()) {
$info = openssl_x509_parse($certificate, false);
}

if (!isset($info['subject']['commonName'])) {
return;
}

$commonName = strtolower($info['subject']['commonName']);
$subjectAltNames = array();

if (isset($info['extensions']['subjectAltName'])) {
$subjectAltNames = preg_split('{\s*,\s*}', $info['extensions']['subjectAltName']);
$subjectAltNames = array_filter(array_map(function ($name) {
if (0 === strpos($name, 'DNS:')) {
return strtolower(ltrim(substr($name, 4)));
}
}, $subjectAltNames));
$subjectAltNames = array_values($subjectAltNames);
}

return array(
'cn' => $commonName,
'san' => $subjectAltNames,
);
}








































public static function getCertificateFingerprint($certificate)
{
$pubkeydetails = openssl_pkey_get_details(openssl_get_publickey($certificate));
$pubkeypem = $pubkeydetails['key'];

 $start = '-----BEGIN PUBLIC KEY-----';
$end = '-----END PUBLIC KEY-----';
$pemtrim = substr($pubkeypem, (strpos($pubkeypem, $start) + strlen($start)), (strlen($pubkeypem) - strpos($pubkeypem, $end)) * (-1));
$der = base64_decode($pemtrim);

return sha1($der);
}









public static function isOpensslParseSafe()
{
if (null !== self::$useOpensslParse) {
return self::$useOpensslParse;
}

if (PHP_VERSION_ID >= 50600) {
return self::$useOpensslParse = true;
}


 
 
 
 if (
(PHP_VERSION_ID < 50400 && PHP_VERSION_ID >= 50328)
|| (PHP_VERSION_ID < 50500 && PHP_VERSION_ID >= 50423)
|| (PHP_VERSION_ID < 50600 && PHP_VERSION_ID >= 50507)
) {

 return self::$useOpensslParse = true;
}

if (Platform::isWindows()) {

 return self::$useOpensslParse = false;
}

$compareDistroVersionPrefix = function ($prefix, $fixedVersion) {
$regex = '{^'.preg_quote($prefix).'([0-9]+)$}';

if (preg_match($regex, PHP_VERSION, $m)) {
return ((int) $m[1]) >= $fixedVersion;
}

return false;
};


 if (
$compareDistroVersionPrefix('5.3.3-7+squeeze', 18) 
 || $compareDistroVersionPrefix('5.4.4-14+deb7u', 7) 
 || $compareDistroVersionPrefix('5.3.10-1ubuntu3.', 9) 
 ) {
return self::$useOpensslParse = true;
}


 
 
 
 
 


 
 $cert = 'LS0tLS1CRUdJTiBDRVJUSUZJQ0FURS0tLS0tCk1JSUVwRENDQTR5Z0F3SUJBZ0lKQUp6dThyNnU2ZUJjTUEwR0NTcUdTSWIzRFFFQkJRVUFNSUhETVFzd0NRWUQKVlFRR0V3SkVSVEVjTUJvR0ExVUVDQXdUVG05eVpISm9aV2x1TFZkbGMzUm1ZV3hsYmpFUU1BNEdBMVVFQnd3SApTOE9Ed3Jac2JqRVVNQklHQTFVRUNnd0xVMlZyZEdsdmJrVnBibk14SHpBZEJnTlZCQXNNRmsxaGJHbGphVzkxCmN5QkRaWEowSUZObFkzUnBiMjR4SVRBZkJnTlZCQU1NR0cxaGJHbGphVzkxY3k1elpXdDBhVzl1WldsdWN5NWsKWlRFcU1DZ0dDU3FHU0liM0RRRUpBUlliYzNSbFptRnVMbVZ6YzJWeVFITmxhM1JwYjI1bGFXNXpMbVJsTUhVWQpaREU1TnpBd01UQXhNREF3TURBd1dnQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBCkFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUEKQUFBQUFBQVhEVEUwTVRFeU9ERXhNemt6TlZvd2djTXhDekFKQmdOVkJBWVRBa1JGTVJ3d0dnWURWUVFJREJOTwpiM0prY21obGFXNHRWMlZ6ZEdaaGJHVnVNUkF3RGdZRFZRUUhEQWRMdzRQQ3RteHVNUlF3RWdZRFZRUUtEQXRUClpXdDBhVzl1UldsdWN6RWZNQjBHQTFVRUN3d1dUV0ZzYVdOcGIzVnpJRU5sY25RZ1UyVmpkR2x2YmpFaE1COEcKQTFVRUF3d1liV0ZzYVdOcGIzVnpMbk5sYTNScGIyNWxhVzV6TG1SbE1Tb3dLQVlKS29aSWh2Y05BUWtCRmh0egpkR1ZtWVc0dVpYTnpaWEpBYzJWcmRHbHZibVZwYm5NdVpHVXdnZ0VpTUEwR0NTcUdTSWIzRFFFQkFRVUFBNElCCkR3QXdnZ0VLQW9JQkFRRERBZjNobDdKWTBYY0ZuaXlFSnBTU0RxbjBPcUJyNlFQNjV1c0pQUnQvOFBhRG9xQnUKd0VZVC9OYSs2ZnNnUGpDMHVLOURaZ1dnMnRIV1dvYW5TYmxBTW96NVBINlorUzRTSFJaN2UyZERJalBqZGhqaAowbUxnMlVNTzV5cDBWNzk3R2dzOWxOdDZKUmZIODFNTjJvYlhXczROdHp0TE11RDZlZ3FwcjhkRGJyMzRhT3M4CnBrZHVpNVVhd1Raa3N5NXBMUEhxNWNNaEZHbTA2djY1Q0xvMFYyUGQ5K0tBb2tQclBjTjVLTEtlYno3bUxwazYKU01lRVhPS1A0aWRFcXh5UTdPN2ZCdUhNZWRzUWh1K3ByWTNzaTNCVXlLZlF0UDVDWm5YMmJwMHdLSHhYMTJEWAoxbmZGSXQ5RGJHdkhUY3lPdU4rblpMUEJtM3ZXeG50eUlJdlZBZ01CQUFHalFqQkFNQWtHQTFVZEV3UUNNQUF3CkVRWUpZSVpJQVliNFFnRUJCQVFEQWdlQU1Bc0dBMVVkRHdRRUF3SUZvREFUQmdOVkhTVUVEREFLQmdnckJnRUYKQlFjREFqQU5CZ2txaGtpRzl3MEJBUVVGQUFPQ0FRRUFHMGZaWVlDVGJkajFYWWMrMVNub2FQUit2SThDOENhRAo4KzBVWWhkbnlVNGdnYTBCQWNEclk5ZTk0ZUVBdTZacXljRjZGakxxWFhkQWJvcHBXb2NyNlQ2R0QxeDMzQ2tsClZBcnpHL0t4UW9oR0QySmVxa2hJTWxEb214SE83a2EzOStPYThpMnZXTFZ5alU4QVp2V01BcnVIYTRFRU55RzcKbFcyQWFnYUZLRkNyOVRuWFRmcmR4R1ZFYnY3S1ZRNmJkaGc1cDVTanBXSDErTXEwM3VSM1pYUEJZZHlWODMxOQpvMGxWajFLRkkyRENML2xpV2lzSlJvb2YrMWNSMzVDdGQwd1lCY3BCNlRac2xNY09QbDc2ZHdLd0pnZUpvMlFnClpzZm1jMnZDMS9xT2xOdU5xLzBUenprVkd2OEVUVDNDZ2FVK1VYZTRYT1Z2a2NjZWJKbjJkZz09Ci0tLS0tRU5EIENFUlRJRklDQVRFLS0tLS0K';
$script = <<<'EOT'

error_reporting(-1);
$info = openssl_x509_parse(base64_decode('%s'));
var_dump(PHP_VERSION, $info['issuer']['emailAddress'], $info['validFrom_time_t']);

EOT;
$script = '<'."?php\n".sprintf($script, $cert);

try {
$process = new PhpProcess($script);
$process->mustRun();
} catch (\Exception $e) {

 
 return self::$useOpensslParse = false;
}

$output = preg_split('{\r?\n}', trim($process->getOutput()));
$errorOutput = trim($process->getErrorOutput());

if (
count($output) === 3
&& $output[0] === sprintf('string(%d) "%s"', strlen(PHP_VERSION), PHP_VERSION)
&& $output[1] === 'string(27) "stefan.esser@sektioneins.de"'
&& $output[2] === 'int(-1)'
&& preg_match('{openssl_x509_parse\(\): illegal (?:ASN1 data type for|length in) timestamp in - on line \d+}', $errorOutput)
) {

 return self::$useOpensslParse = true;
}

return self::$useOpensslParse = false;
}








private static function certNameMatcher($certName)
{
$wildcards = substr_count($certName, '*');

if (0 === $wildcards) {

 return function ($hostname) use ($certName) {
return $hostname === $certName;
};
}

if (1 === $wildcards) {
$components = explode('.', $certName);

if (3 > count($components)) {

 return;
}

$firstComponent = $components[0];


 if ('*' !== $firstComponent[strlen($firstComponent) - 1]) {
return;
}

$wildcardRegex = preg_quote($certName);
$wildcardRegex = str_replace('\\*', '[a-z0-9-]+', $wildcardRegex);
$wildcardRegex = "{^{$wildcardRegex}$}";

return function ($hostname) use ($wildcardRegex) {
return 1 === preg_match($wildcardRegex, $hostname);
};
}
}
}
