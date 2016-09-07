<?php











namespace Composer;

use Composer\Config\JsonConfigSource;
use Composer\Json\JsonFile;
use Composer\IO\IOInterface;
use Composer\Package\Archiver;
use Composer\Package\Version\VersionGuesser;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Util\Filesystem;
use Composer\Util\Platform;
use Composer\Util\ProcessExecutor;
use Composer\Util\RemoteFilesystem;
use Composer\Util\Silencer;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Autoload\AutoloadGenerator;
use Composer\Semver\VersionParser;
use Composer\Downloader\TransportException;
use Seld\JsonLint\JsonParser;









class Factory
{




protected static function getHomeDir()
{
$home = getenv('COMPOSER_HOME');
if ($home) {
return $home;
}

if (Platform::isWindows()) {
if (!getenv('APPDATA')) {
throw new \RuntimeException('The APPDATA or COMPOSER_HOME environment variable must be set for composer to run correctly');
}

return rtrim(strtr(getenv('APPDATA'), '\\', '/'), '/') . '/Composer';
}

$userDir = self::getUserDir();
if (is_dir($userDir . '/.composer')) {
return $userDir . '/.composer';
}

if (self::useXdg()) {

 $xdgConfig = getenv('XDG_CONFIG_HOME') ?: $userDir . '/.config';

return $xdgConfig . '/composer';
}

return $userDir . '/.composer';
}





protected static function getCacheDir($home)
{
$cacheDir = getenv('COMPOSER_CACHE_DIR');
if ($cacheDir) {
return $cacheDir;
}

$homeEnv = getenv('COMPOSER_HOME');
if ($homeEnv) {
return $homeEnv . '/cache';
}

if (Platform::isWindows()) {
if ($cacheDir = getenv('LOCALAPPDATA')) {
$cacheDir .= '/Composer';
} else {
$cacheDir = $home . '/cache';
}

return rtrim(strtr($cacheDir, '\\', '/'), '/');
}

$userDir = self::getUserDir();
if ($home === $userDir . '/.composer' && is_dir($home . '/cache')) {
return $home . '/cache';
}

if (self::useXdg()) {
$xdgCache = getenv('XDG_CACHE_HOME') ?: $userDir . '/.cache';

return $xdgCache . '/composer';
}

return $home . '/cache';
}





protected static function getDataDir($home)
{
$homeEnv = getenv('COMPOSER_HOME');
if ($homeEnv) {
return $homeEnv;
}

if (Platform::isWindows()) {
return strtr($home, '\\', '/');
}

$userDir = self::getUserDir();
if ($home !== $userDir . '/.composer' && self::useXdg()) {
$xdgData = getenv('XDG_DATA_HOME') ?: $userDir . '/.local/share';

return $xdgData . '/composer';
}

return $home;
}





public static function createConfig(IOInterface $io = null, $cwd = null)
{
$cwd = $cwd ?: getcwd();

$config = new Config(true, $cwd);


 $home = self::getHomeDir();
$config->merge(array('config' => array(
'home' => $home,
'cache-dir' => self::getCacheDir($home),
'data-dir' => self::getDataDir($home),
)));


 
 
 $dirs = array($config->get('home'), $config->get('cache-dir'), $config->get('data-dir'));
foreach ($dirs as $dir) {
if (!file_exists($dir . '/.htaccess')) {
if (!is_dir($dir)) {
Silencer::call('mkdir', $dir, 0777, true);
}
Silencer::call('file_put_contents', $dir . '/.htaccess', 'Deny from all');
}
}


 $file = new JsonFile($config->get('home').'/config.json');
if ($file->exists()) {
if ($io && $io->isDebug()) {
$io->writeError('Loading config file ' . $file->getPath());
}
$config->merge($file->read());
}
$config->setConfigSource(new JsonConfigSource($file));


 $file = new JsonFile($config->get('home').'/auth.json');
if ($file->exists()) {
if ($io && $io->isDebug()) {
$io->writeError('Loading config file ' . $file->getPath());
}
$config->merge(array('config' => $file->read()));
}
$config->setAuthConfigSource(new JsonConfigSource($file, true));


 if ($composerAuthEnv = getenv('COMPOSER_AUTH')) {
$authData = json_decode($composerAuthEnv, true);

if (is_null($authData)) {
throw new \UnexpectedValueException('COMPOSER_AUTH environment variable is malformed, should be a valid JSON object');
}

if ($io && $io->isDebug()) {
$io->writeError('Loading auth config from COMPOSER_AUTH');
}
$config->merge(array('config' => $authData));
}

return $config;
}

public static function getComposerFile()
{
return trim(getenv('COMPOSER')) ?: './composer.json';
}

public static function createAdditionalStyles()
{
return array(
'highlight' => new OutputFormatterStyle('red'),
'warning' => new OutputFormatterStyle('black', 'yellow'),
);
}

public static function createDefaultRepositories(IOInterface $io = null, Config $config = null, RepositoryManager $rm = null)
{
$repos = array();

if (!$config) {
$config = static::createConfig($io);
}
if (!$rm) {
if (!$io) {
throw new \InvalidArgumentException('This function requires either an IOInterface or a RepositoryManager');
}
$factory = new static;
$rm = $factory->createRepositoryManager($io, $config, null, self::createRemoteFilesystem($io, $config));
}

foreach ($config->getRepositories() as $index => $repo) {
if (is_string($repo)) {
throw new \UnexpectedValueException('"repositories" should be an array of repository definitions, only a single repository was given');
}
if (!is_array($repo)) {
throw new \UnexpectedValueException('Repository "'.$index.'" ('.json_encode($repo).') should be an array, '.gettype($repo).' given');
}
if (!isset($repo['type'])) {
throw new \UnexpectedValueException('Repository "'.$index.'" ('.json_encode($repo).') must have a type defined');
}
$name = is_int($index) && isset($repo['url']) ? preg_replace('{^https?://}i', '', $repo['url']) : $index;
while (isset($repos[$name])) {
$name .= '2';
}
$repos[$name] = $rm->createRepository($repo['type'], $repo);
}

return $repos;
}













public function createComposer(IOInterface $io, $localConfig = null, $disablePlugins = false, $cwd = null, $fullLoad = true)
{
$cwd = $cwd ?: getcwd();


 if (null === $localConfig) {
$localConfig = static::getComposerFile();
}

if (is_string($localConfig)) {
$composerFile = $localConfig;

$file = new JsonFile($localConfig, null, $io);

if (!$file->exists()) {
if ($localConfig === './composer.json' || $localConfig === 'composer.json') {
$message = 'Composer could not find a composer.json file in '.$cwd;
} else {
$message = 'Composer could not find the config file: '.$localConfig;
}
$instructions = 'To initialize a project, please create a composer.json file as described in the https://getcomposer.org/ "Getting Started" section';
throw new \InvalidArgumentException($message.PHP_EOL.$instructions);
}

$file->validateSchema(JsonFile::LAX_SCHEMA);
$jsonParser = new JsonParser;
try {
$jsonParser->parse(file_get_contents($localConfig), JsonParser::DETECT_KEY_CONFLICTS);
} catch (\Seld\JsonLint\DuplicateKeyException $e) {
$details = $e->getDetails();
$io->writeError('<warning>Key '.$details['key'].' is a duplicate in '.$localConfig.' at line '.$details['line'].'</warning>');
}

$localConfig = $file->read();
}


 $config = static::createConfig($io, $cwd);
$config->merge($localConfig);
if (isset($composerFile)) {
$io->writeError('Loading config file ' . $composerFile, true, IOInterface::DEBUG);
$localAuthFile = new JsonFile(dirname(realpath($composerFile)) . '/auth.json');
if ($localAuthFile->exists()) {
$io->writeError('Loading config file ' . $localAuthFile->getPath(), true, IOInterface::DEBUG);
$config->merge(array('config' => $localAuthFile->read()));
$config->setAuthConfigSource(new JsonConfigSource($localAuthFile, true));
}
}

$vendorDir = $config->get('vendor-dir');
$binDir = $config->get('bin-dir');


 $composer = new Composer();
$composer->setConfig($config);

if ($fullLoad) {

 $io->loadConfiguration($config);
}

$rfs = self::createRemoteFilesystem($io, $config);


 $dispatcher = new EventDispatcher($composer, $io);
$composer->setEventDispatcher($dispatcher);


 $rm = $this->createRepositoryManager($io, $config, $dispatcher, $rfs);
$composer->setRepositoryManager($rm);


 $this->addLocalRepository($io, $rm, $vendorDir);


 
 if (!$fullLoad && !isset($localConfig['version'])) {
$localConfig['version'] = '1.0.0';
}


 $parser = new VersionParser;
$guesser = new VersionGuesser($config, new ProcessExecutor($io), $parser);
$loader = new Package\Loader\RootPackageLoader($rm, $config, $parser, $guesser);
$package = $loader->load($localConfig, 'Composer\Package\RootPackage', $cwd);
$composer->setPackage($package);


 $im = $this->createInstallationManager();
$composer->setInstallationManager($im);

if ($fullLoad) {

 $dm = $this->createDownloadManager($io, $config, $dispatcher, $rfs);
$composer->setDownloadManager($dm);


 $generator = new AutoloadGenerator($dispatcher, $io);
$composer->setAutoloadGenerator($generator);
}


 $this->createDefaultInstallers($im, $composer, $io);

if ($fullLoad) {
$globalComposer = $this->createGlobalComposer($io, $config, $disablePlugins);
$pm = $this->createPluginManager($io, $composer, $globalComposer, $disablePlugins);
$composer->setPluginManager($pm);

$pm->loadInstalledPlugins();


 
 if ($rm->getLocalRepository()) {
$this->purgePackages($rm->getLocalRepository(), $im);
}
}


 if ($fullLoad && isset($composerFile)) {
$lockFile = "json" === pathinfo($composerFile, PATHINFO_EXTENSION)
? substr($composerFile, 0, -4).'lock'
: $composerFile . '.lock';

$locker = new Package\Locker($io, new JsonFile($lockFile, null, $io), $rm, $im, file_get_contents($composerFile));
$composer->setLocker($locker);
}

return $composer;
}







protected function createRepositoryManager(IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null, RemoteFilesystem $rfs = null)
{
$rm = new RepositoryManager($io, $config, $eventDispatcher, $rfs);
$rm->setRepositoryClass('composer', 'Composer\Repository\ComposerRepository');
$rm->setRepositoryClass('vcs', 'Composer\Repository\VcsRepository');
$rm->setRepositoryClass('package', 'Composer\Repository\PackageRepository');
$rm->setRepositoryClass('pear', 'Composer\Repository\PearRepository');
$rm->setRepositoryClass('git', 'Composer\Repository\VcsRepository');
$rm->setRepositoryClass('gitlab', 'Composer\Repository\VcsRepository');
$rm->setRepositoryClass('svn', 'Composer\Repository\VcsRepository');
$rm->setRepositoryClass('perforce', 'Composer\Repository\VcsRepository');
$rm->setRepositoryClass('hg', 'Composer\Repository\VcsRepository');
$rm->setRepositoryClass('artifact', 'Composer\Repository\ArtifactRepository');
$rm->setRepositoryClass('path', 'Composer\Repository\PathRepository');

return $rm;
}





protected function addLocalRepository(IOInterface $io, RepositoryManager $rm, $vendorDir)
{
$rm->setLocalRepository(new Repository\InstalledFilesystemRepository(new JsonFile($vendorDir.'/composer/installed.json', null, $io)));
}





protected function createGlobalComposer(IOInterface $io, Config $config, $disablePlugins)
{
if (realpath($config->get('home')) === getcwd()) {
return;
}

$composer = null;
try {
$composer = self::createComposer($io, $config->get('home') . '/composer.json', $disablePlugins, $config->get('home'), false);
} catch (\Exception $e) {
$io->writeError('Failed to initialize global composer: '.$e->getMessage(), true, IOInterface::DEBUG);
}

return $composer;
}







public function createDownloadManager(IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null, RemoteFilesystem $rfs = null)
{
$cache = null;
if ($config->get('cache-files-ttl') > 0) {
$cache = new Cache($io, $config->get('cache-files-dir'), 'a-z0-9_./');
}

$dm = new Downloader\DownloadManager($io);
switch ($config->get('preferred-install')) {
case 'dist':
$dm->setPreferDist(true);
break;
case 'source':
$dm->setPreferSource(true);
break;
case 'auto':
default:

 break;
}

$executor = new ProcessExecutor($io);
$fs = new Filesystem($executor);

$dm->setDownloader('git', new Downloader\GitDownloader($io, $config, $executor, $fs));
$dm->setDownloader('svn', new Downloader\SvnDownloader($io, $config, $executor, $fs));
$dm->setDownloader('hg', new Downloader\HgDownloader($io, $config, $executor, $fs));
$dm->setDownloader('perforce', new Downloader\PerforceDownloader($io, $config));
$dm->setDownloader('zip', new Downloader\ZipDownloader($io, $config, $eventDispatcher, $cache, $executor, $rfs));
$dm->setDownloader('rar', new Downloader\RarDownloader($io, $config, $eventDispatcher, $cache, $executor, $rfs));
$dm->setDownloader('tar', new Downloader\TarDownloader($io, $config, $eventDispatcher, $cache, $rfs));
$dm->setDownloader('gzip', new Downloader\GzipDownloader($io, $config, $eventDispatcher, $cache, $executor, $rfs));
$dm->setDownloader('xz', new Downloader\XzDownloader($io, $config, $eventDispatcher, $cache, $executor, $rfs));
$dm->setDownloader('phar', new Downloader\PharDownloader($io, $config, $eventDispatcher, $cache, $rfs));
$dm->setDownloader('file', new Downloader\FileDownloader($io, $config, $eventDispatcher, $cache, $rfs));
$dm->setDownloader('path', new Downloader\PathDownloader($io, $config, $eventDispatcher, $cache, $rfs));

return $dm;
}






public function createArchiveManager(Config $config, Downloader\DownloadManager $dm = null)
{
if (null === $dm) {
$io = new IO\NullIO();
$io->loadConfiguration($config);
$dm = $this->createDownloadManager($io, $config);
}

$am = new Archiver\ArchiveManager($dm);
$am->addArchiver(new Archiver\ZipArchiver);
$am->addArchiver(new Archiver\PharArchiver);

return $am;
}








protected function createPluginManager(IOInterface $io, Composer $composer, Composer $globalComposer = null, $disablePlugins = false)
{
return new Plugin\PluginManager($io, $composer, $globalComposer, $disablePlugins);
}




protected function createInstallationManager()
{
return new Installer\InstallationManager();
}






protected function createDefaultInstallers(Installer\InstallationManager $im, Composer $composer, IOInterface $io)
{
$im->addInstaller(new Installer\LibraryInstaller($io, $composer, null));
$im->addInstaller(new Installer\PearInstaller($io, $composer, 'pear-library'));
$im->addInstaller(new Installer\PluginInstaller($io, $composer));
$im->addInstaller(new Installer\MetapackageInstaller($io));
}





protected function purgePackages(WritableRepositoryInterface $repo, Installer\InstallationManager $im)
{
foreach ($repo->getPackages() as $package) {
if (!$im->isPackageInstalled($repo, $package)) {
$repo->removePackage($package);
}
}
}








public static function create(IOInterface $io, $config = null, $disablePlugins = false)
{
$factory = new static();

return $factory->createComposer($io, $config, $disablePlugins);
}







public static function createRemoteFilesystem(IOInterface $io, Config $config = null, $options = array())
{
static $warned = false;
$disableTls = false;
if ($config && $config->get('disable-tls') === true) {
if (!$warned) {
$io->write('<warning>You are running Composer with SSL/TLS protection disabled.</warning>');
}
$warned = true;
$disableTls = true;
} elseif (!extension_loaded('openssl')) {
throw new \RuntimeException('The openssl extension is required for SSL/TLS protection but is not available. '
. 'If you can not enable the openssl extension, you can disable this error, at your own risk, by setting the \'disable-tls\' option to true.');
}
$remoteFilesystemOptions = array();
if ($disableTls === false) {
if ($config && $config->get('cafile')) {
$remoteFilesystemOptions['ssl']['cafile'] = $config->get('cafile');
}
if ($config && $config->get('capath')) {
$remoteFilesystemOptions['ssl']['capath'] = $config->get('capath');
}
$remoteFilesystemOptions = array_replace_recursive($remoteFilesystemOptions, $options);
}
try {
$remoteFilesystem = new RemoteFilesystem($io, $config, $remoteFilesystemOptions, $disableTls);
} catch (TransportException $e) {
if (false !== strpos($e->getMessage(), 'cafile')) {
$io->write('<error>Unable to locate a valid CA certificate file. You must set a valid \'cafile\' option.</error>');
$io->write('<error>A valid CA certificate file is required for SSL/TLS protection.</error>');
if (PHP_VERSION_ID < 50600) {
$io->write('<error>It is recommended you upgrade to PHP 5.6+ which can detect your system CA file automatically.</error>');
}
$io->write('<error>You can disable this error, at your own risk, by setting the \'disable-tls\' option to true.</error>');
}
throw $e;
}

return $remoteFilesystem;
}




private static function useXdg()
{
foreach (array_keys($_SERVER) as $key) {
if (substr($key, 0, 4) === 'XDG_') {
return true;
}
}

return false;
}





private static function getUserDir()
{
$home = getenv('HOME');
if (!$home) {
throw new \RuntimeException('The HOME or COMPOSER_HOME environment variable must be set for composer to run correctly');
}

return rtrim(strtr($home, '\\', '/'), '/');
}
}
