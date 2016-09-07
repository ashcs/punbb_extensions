<?php










namespace Symfony\Component\Filesystem\Exception;








class IOException extends \RuntimeException implements IOExceptionInterface
{
private $path;

public function __construct($message, $code = 0, \Exception $previous = null, $path = null)
{
$this->path = $path;

parent::__construct($message, $code, $previous);
}




public function getPath()
{
return $this->path;
}
}
