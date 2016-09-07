<?php










namespace Symfony\Component\Console\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;














class ConsoleOutput extends StreamOutput implements ConsoleOutputInterface
{



private $stderr;








public function __construct($verbosity = self::VERBOSITY_NORMAL, $decorated = null, OutputFormatterInterface $formatter = null)
{
parent::__construct($this->openOutputStream(), $verbosity, $decorated, $formatter);

$actualDecorated = $this->isDecorated();
$this->stderr = new StreamOutput($this->openErrorStream(), $verbosity, $decorated, $this->getFormatter());

if (null === $decorated) {
$this->setDecorated($actualDecorated && $this->stderr->isDecorated());
}
}




public function setDecorated($decorated)
{
parent::setDecorated($decorated);
$this->stderr->setDecorated($decorated);
}




public function setFormatter(OutputFormatterInterface $formatter)
{
parent::setFormatter($formatter);
$this->stderr->setFormatter($formatter);
}




public function setVerbosity($level)
{
parent::setVerbosity($level);
$this->stderr->setVerbosity($level);
}




public function getErrorOutput()
{
return $this->stderr;
}




public function setErrorOutput(OutputInterface $error)
{
$this->stderr = $error;
}







protected function hasStdoutSupport()
{
return false === $this->isRunningOS400();
}







protected function hasStderrSupport()
{
return false === $this->isRunningOS400();
}







private function isRunningOS400()
{
$checks = array(
function_exists('php_uname') ? php_uname('s') : '',
getenv('OSTYPE'),
PHP_OS,
);

return false !== stripos(implode(';', $checks), 'OS400');
}




private function openOutputStream()
{
$outputStream = $this->hasStdoutSupport() ? 'php://stdout' : 'php://output';

return @fopen($outputStream, 'w') ?: fopen('php://output', 'w');
}




private function openErrorStream()
{
$errorStream = $this->hasStderrSupport() ? 'php://stderr' : 'php://output';

return fopen($errorStream, 'w');
}
}
