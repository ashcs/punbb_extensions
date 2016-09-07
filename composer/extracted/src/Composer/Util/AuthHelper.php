<?php











namespace Composer\Util;

use Composer\Config;
use Composer\IO\IOInterface;




class AuthHelper
{
protected $io;
protected $config;

public function __construct(IOInterface $io, Config $config)
{
$this->io = $io;
$this->config = $config;
}

public function storeAuth($originUrl, $storeAuth)
{
$store = false;
$configSource = $this->config->getAuthConfigSource();
if ($storeAuth === true) {
$store = $configSource;
} elseif ($storeAuth === 'prompt') {
$answer = $this->io->askAndValidate(
'Do you want to store credentials for '.$originUrl.' in '.$configSource->getName().' ? [Yn] ',
function ($value) {
$input = strtolower(substr(trim($value), 0, 1));
if (in_array($input, array('y','n'))) {
return $input;
}
throw new \RuntimeException('Please answer (y)es or (n)o');
},
null,
'y'
);

if ($answer === 'y') {
$store = $configSource;
}
}
if ($store) {
$store->addConfigSetting(
'http-basic.'.$originUrl,
$this->io->getAuthentication($originUrl)
);
}
}
}
