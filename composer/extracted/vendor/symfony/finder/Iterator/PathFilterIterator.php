<?php










namespace Symfony\Component\Finder\Iterator;







class PathFilterIterator extends MultiplePcreFilterIterator
{





public function accept()
{
$filename = $this->current()->getRelativePathname();

if ('\\' === DIRECTORY_SEPARATOR) {
$filename = str_replace('\\', '/', $filename);
}

return $this->isAccepted($filename);
}















protected function toRegex($str)
{
return $this->isRegex($str) ? $str : '/'.preg_quote($str, '/').'/';
}
}
