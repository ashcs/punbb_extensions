<?php










namespace Symfony\Component\Console\Style;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;






abstract class OutputStyle implements OutputInterface, StyleInterface
{
private $output;




public function __construct(OutputInterface $output)
{
$this->output = $output;
}




public function newLine($count = 1)
{
$this->output->write(str_repeat(PHP_EOL, $count));
}






public function createProgressBar($max = 0)
{
return new ProgressBar($this->output, $max);
}




public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL)
{
$this->output->write($messages, $newline, $type);
}




public function writeln($messages, $type = self::OUTPUT_NORMAL)
{
$this->output->writeln($messages, $type);
}




public function setVerbosity($level)
{
$this->output->setVerbosity($level);
}




public function getVerbosity()
{
return $this->output->getVerbosity();
}




public function setDecorated($decorated)
{
$this->output->setDecorated($decorated);
}




public function isDecorated()
{
return $this->output->isDecorated();
}




public function setFormatter(OutputFormatterInterface $formatter)
{
$this->output->setFormatter($formatter);
}




public function getFormatter()
{
return $this->output->getFormatter();
}
}
