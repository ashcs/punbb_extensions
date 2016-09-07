<?php











namespace Composer\IO;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;







class ConsoleIO extends BaseIO
{
protected $input;
protected $output;
protected $helperSet;
protected $lastMessage;
protected $lastMessageErr;
private $startTime;
private $verbosityMap;








public function __construct(InputInterface $input, OutputInterface $output, HelperSet $helperSet)
{
$this->input = $input;
$this->output = $output;
$this->helperSet = $helperSet;
$this->verbosityMap = array(
self::QUIET => OutputInterface::VERBOSITY_QUIET,
self::NORMAL => OutputInterface::VERBOSITY_NORMAL,
self::VERBOSE => OutputInterface::VERBOSITY_VERBOSE,
self::VERY_VERBOSE => OutputInterface::VERBOSITY_VERY_VERBOSE,
self::DEBUG => OutputInterface::VERBOSITY_DEBUG,
);
}

public function enableDebugging($startTime)
{
$this->startTime = $startTime;
}




public function isInteractive()
{
return $this->input->isInteractive();
}




public function isDecorated()
{
return $this->output->isDecorated();
}




public function isVerbose()
{
return $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
}




public function isVeryVerbose()
{
return $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE;
}




public function isDebug()
{
return $this->output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG;
}




public function write($messages, $newline = true, $verbosity = self::NORMAL)
{
$this->doWrite($messages, $newline, false, $verbosity);
}




public function writeError($messages, $newline = true, $verbosity = self::NORMAL)
{
$this->doWrite($messages, $newline, true, $verbosity);
}







private function doWrite($messages, $newline, $stderr, $verbosity)
{
$sfVerbosity = $this->verbosityMap[$verbosity];
if ($sfVerbosity > $this->output->getVerbosity()) {
return;
}


 
 
 if (OutputInterface::VERBOSITY_QUIET === 0) {
$sfVerbosity = OutputInterface::OUTPUT_NORMAL;
}

if (null !== $this->startTime) {
$memoryUsage = memory_get_usage() / 1024 / 1024;
$timeSpent = microtime(true) - $this->startTime;
$messages = array_map(function ($message) use ($memoryUsage, $timeSpent) {
return sprintf('[%.1fMB/%.2fs] %s', $memoryUsage, $timeSpent, $message);
}, (array) $messages);
}

if (true === $stderr && $this->output instanceof ConsoleOutputInterface) {
$this->output->getErrorOutput()->write($messages, $newline, $sfVerbosity);
$this->lastMessageErr = join($newline ? "\n" : '', (array) $messages);

return;
}

$this->output->write($messages, $newline, $sfVerbosity);
$this->lastMessage = join($newline ? "\n" : '', (array) $messages);
}




public function overwrite($messages, $newline = true, $size = null, $verbosity = self::NORMAL)
{
$this->doOverwrite($messages, $newline, $size, false, $verbosity);
}




public function overwriteError($messages, $newline = true, $size = null, $verbosity = self::NORMAL)
{
$this->doOverwrite($messages, $newline, $size, true, $verbosity);
}








private function doOverwrite($messages, $newline, $size, $stderr, $verbosity)
{

 $messages = join($newline ? "\n" : '', (array) $messages);


 if (!isset($size)) {

 $size = strlen(strip_tags($stderr ? $this->lastMessageErr : $this->lastMessage));
}

 $this->doWrite(str_repeat("\x08", $size), false, $stderr, $verbosity);


 $this->doWrite($messages, false, $stderr, $verbosity);

$fill = $size - strlen(strip_tags($messages));
if ($fill > 0) {

 $this->doWrite(str_repeat(' ', $fill), false, $stderr, $verbosity);

 $this->doWrite(str_repeat("\x08", $fill), false, $stderr, $verbosity);
}

if ($newline) {
$this->doWrite('', true, $stderr, $verbosity);
}

if ($stderr) {
$this->lastMessageErr = $messages;
} else {
$this->lastMessage = $messages;
}
}




public function ask($question, $default = null)
{
$output = $this->output;

if ($output instanceof ConsoleOutputInterface) {
$output = $output->getErrorOutput();
}


$helper = $this->helperSet->get('question');
$question = new Question($question, $default);

return $helper->ask($this->input, $output, $question);
}




public function askConfirmation($question, $default = true)
{
$output = $this->output;

if ($output instanceof ConsoleOutputInterface) {
$output = $output->getErrorOutput();
}


$helper = $this->helperSet->get('question');
$question = new ConfirmationQuestion($question, $default);

return $helper->ask($this->input, $output, $question);
}




public function askAndValidate($question, $validator, $attempts = null, $default = null)
{
$output = $this->output;

if ($output instanceof ConsoleOutputInterface) {
$output = $output->getErrorOutput();
}


$helper = $this->helperSet->get('question');
$question = new Question($question, $default);
$question->setValidator($validator);
$question->setMaxAttempts($attempts);

return $helper->ask($this->input, $output, $question);
}




public function askAndHideAnswer($question)
{
$this->writeError($question, false);

return \Seld\CliPrompt\CliPrompt::hiddenPrompt(true);
}
}
