<?php










namespace Symfony\Component\Filesystem\Exception;







class FileNotFoundException extends IOException
{
public function __construct($message = null, $code = 0, \Exception $previous = null, $path = null)
{
if (null === $message) {
if (null === $path) {
$message = 'File could not be found.';
} else {
$message = sprintf('File "%s" could not be found.', $path);
}
}

parent::__construct($message, $code, $previous, $path);
}
}
