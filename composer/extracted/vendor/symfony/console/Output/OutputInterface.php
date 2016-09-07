<?php










namespace Symfony\Component\Console\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;






interface OutputInterface
{
const VERBOSITY_QUIET = 16;
const VERBOSITY_NORMAL = 32;
const VERBOSITY_VERBOSE = 64;
const VERBOSITY_VERY_VERBOSE = 128;
const VERBOSITY_DEBUG = 256;

const OUTPUT_NORMAL = 1;
const OUTPUT_RAW = 2;
const OUTPUT_PLAIN = 4;








public function write($messages, $newline = false, $options = 0);







public function writeln($messages, $options = 0);






public function setVerbosity($level);






public function getVerbosity();






public function setDecorated($decorated);






public function isDecorated();






public function setFormatter(OutputFormatterInterface $formatter);






public function getFormatter();
}
