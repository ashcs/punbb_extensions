<?php










namespace Symfony\Component\Console\Exception;






class CommandNotFoundException extends \InvalidArgumentException implements ExceptionInterface
{
private $alternatives;







public function __construct($message, array $alternatives = array(), $code = 0, \Exception $previous = null)
{
parent::__construct($message, $code, $previous);

$this->alternatives = $alternatives;
}




public function getAlternatives()
{
return $this->alternatives;
}
}
