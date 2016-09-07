<?php











namespace Composer\Package\Archiver;

use ZipArchive;




class ZipArchiver implements ArchiverInterface
{
protected static $formats = array(
'zip' => 1
);




public function archive($sources, $target, $format, array $excludes = array())
{
$sources = realpath($sources);
$zip = new ZipArchive();
$res = $zip->open($target, ZipArchive::CREATE);
if ($res === true) {
$files = new ArchivableFilesFinder($sources, $excludes);
foreach($files as $file) {

$filepath = $file->getPath()."/".$file->getFilename();
$localname = str_replace($sources."/", '', $filepath);
$zip->addFile($filepath, $localname);
}
if ($zip->close()) {
return $target;
}
}
$message = sprintf("Could not create archive '%s' from '%s': %s",
$target,
$sources,
$zip->getStatusString()
);
throw new \RuntimeException($message);
}




public function supports($format, $sourceType)
{
return isset(static::$formats[$format]) && $this->compressionAvailable();
}

private function compressionAvailable() {
return class_exists('ZipArchive');
}
}
