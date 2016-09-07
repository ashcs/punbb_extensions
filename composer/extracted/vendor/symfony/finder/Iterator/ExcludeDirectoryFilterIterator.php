<?php










namespace Symfony\Component\Finder\Iterator;






class ExcludeDirectoryFilterIterator extends FilterIterator implements \RecursiveIterator
{
private $iterator;
private $isRecursive;
private $excludedDirs = array();
private $excludedPattern;







public function __construct(\Iterator $iterator, array $directories)
{
$this->iterator = $iterator;
$this->isRecursive = $iterator instanceof \RecursiveIterator;
$patterns = array();
foreach ($directories as $directory) {
if (!$this->isRecursive || false !== strpos($directory, '/')) {
$patterns[] = preg_quote($directory, '#');
} else {
$this->excludedDirs[$directory] = true;
}
}
if ($patterns) {
$this->excludedPattern = '#(?:^|/)(?:'.implode('|', $patterns).')(?:/|$)#';
}

parent::__construct($iterator);
}






public function accept()
{
if ($this->isRecursive && isset($this->excludedDirs[$this->getFilename()]) && $this->isDir()) {
return false;
}

if ($this->excludedPattern) {
$path = $this->isDir() ? $this->current()->getRelativePathname() : $this->current()->getRelativePath();
$path = str_replace('\\', '/', $path);

return !preg_match($this->excludedPattern, $path);
}

return true;
}

public function hasChildren()
{
return $this->isRecursive && $this->iterator->hasChildren();
}

public function getChildren()
{
$children = new self($this->iterator->getChildren(), array());
$children->excludedDirs = $this->excludedDirs;
$children->excludedPattern = $this->excludedPattern;

return $children;
}
}
