<?php











namespace Composer\Package\Archiver;






class PharArchiver implements ArchiverInterface
{
protected static $formats = array(
'zip' => \Phar::ZIP,
'tar' => \Phar::TAR,
'tar.gz' => \Phar::TAR,
'tar.bz2' => \Phar::TAR
);

protected static $compressFormats = array(
'tar.gz' => \Phar::GZ,
'tar.bz2' => \Phar::BZ2
);




public function archive($sources, $target, $format, array $excludes = array())
{
$sources = realpath($sources);


 if (file_exists($target)) {
unlink($target);
}

try {
$filename = substr($target, 0, strrpos($target, $format) - 1);


 if (isset(static::$compressFormats[$format])) {

 $target = $filename . '.tar';
}

$phar = new \PharData($target, null, null, static::$formats[$format]);
$files = new ArchivableFilesFinder($sources, $excludes);
$phar->buildFromIterator($files, $sources);

if (isset(static::$compressFormats[$format])) {

 if (!$phar->canCompress(static::$compressFormats[$format])) {
throw new \RuntimeException(sprintf('Can not compress to %s format', $format));
}


 unlink($target);


 $phar->compress(static::$compressFormats[$format]);


 $target = $filename . '.' . $format;
}

return $target;
} catch (\UnexpectedValueException $e) {
$message = sprintf("Could not create archive '%s' from '%s': %s",
$target,
$sources,
$e->getMessage()
);

throw new \RuntimeException($message, $e->getCode(), $e);
}
}




public function supports($format, $sourceType)
{
return isset(static::$formats[$format]);
}
}
