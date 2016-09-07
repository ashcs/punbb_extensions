<?php










namespace Symfony\Component\Finder\Iterator;

use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Finder\SplFileInfo;






class RecursiveDirectoryIterator extends \RecursiveDirectoryIterator
{



private $ignoreUnreadableDirs;




private $rewindable;


 private $rootPath;
private $subPath;
private $directorySeparator = '/';










public function __construct($path, $flags, $ignoreUnreadableDirs = false)
{
if ($flags & (self::CURRENT_AS_PATHNAME | self::CURRENT_AS_SELF)) {
throw new \RuntimeException('This iterator only support returning current as fileinfo.');
}

parent::__construct($path, $flags);
$this->ignoreUnreadableDirs = $ignoreUnreadableDirs;
$this->rootPath = (string) $path;
if ('/' !== DIRECTORY_SEPARATOR && !($flags & self::UNIX_PATHS)) {
$this->directorySeparator = DIRECTORY_SEPARATOR;
}
}






public function current()
{


if (null === $subPathname = $this->subPath) {
$subPathname = $this->subPath = (string) $this->getSubPath();
}
if ('' !== $subPathname) {
$subPathname .= $this->directorySeparator;
}
$subPathname .= $this->getFilename();

return new SplFileInfo($this->rootPath.$this->directorySeparator.$subPathname, $this->subPath, $subPathname);
}






public function getChildren()
{
try {
$children = parent::getChildren();

if ($children instanceof self) {

 $children->ignoreUnreadableDirs = $this->ignoreUnreadableDirs;


 $children->rewindable = &$this->rewindable;
$children->rootPath = $this->rootPath;
}

return $children;
} catch (\UnexpectedValueException $e) {
if ($this->ignoreUnreadableDirs) {

 return new \RecursiveArrayIterator(array());
} else {
throw new AccessDeniedException($e->getMessage(), $e->getCode(), $e);
}
}
}




public function rewind()
{
if (false === $this->isRewindable()) {
return;
}


 parent::next();

parent::rewind();
}






public function isRewindable()
{
if (null !== $this->rewindable) {
return $this->rewindable;
}

if (false !== $stream = @opendir($this->getPath())) {
$infos = stream_get_meta_data($stream);
closedir($stream);

if ($infos['seekable']) {
return $this->rewindable = true;
}
}

return $this->rewindable = false;
}
}
