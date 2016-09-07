<?php










namespace Symfony\Component\Console\Style;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;






class SymfonyStyle extends OutputStyle
{
const MAX_LINE_LENGTH = 120;

private $input;
private $questionHelper;
private $progressBar;
private $lineLength;
private $bufferedOutput;





public function __construct(InputInterface $input, OutputInterface $output)
{
$this->input = $input;
$this->bufferedOutput = new BufferedOutput($output->getVerbosity(), false, clone $output->getFormatter());

 $this->lineLength = min($this->getTerminalWidth() - (int) (DIRECTORY_SEPARATOR === '\\'), self::MAX_LINE_LENGTH);

parent::__construct($output);
}










public function block($messages, $type = null, $style = null, $prefix = ' ', $padding = false)
{
$this->autoPrependBlock();
$messages = is_array($messages) ? array_values($messages) : array($messages);
$lines = array();


 if (null !== $type) {
$messages[0] = sprintf('[%s] %s', $type, $messages[0]);
}


 foreach ($messages as $key => $message) {
$message = OutputFormatter::escape($message);
$lines = array_merge($lines, explode(PHP_EOL, wordwrap($message, $this->lineLength - Helper::strlen($prefix), PHP_EOL, true)));

if (count($messages) > 1 && $key < count($messages) - 1) {
$lines[] = '';
}
}

if ($padding && $this->isDecorated()) {
array_unshift($lines, '');
$lines[] = '';
}

foreach ($lines as &$line) {
$line = sprintf('%s%s', $prefix, $line);
$line .= str_repeat(' ', $this->lineLength - Helper::strlenWithoutDecoration($this->getFormatter(), $line));

if ($style) {
$line = sprintf('<%s>%s</>', $style, $line);
}
}

$this->writeln($lines);
$this->newLine();
}




public function title($message)
{
$this->autoPrependBlock();
$this->writeln(array(
sprintf('<comment>%s</>', $message),
sprintf('<comment>%s</>', str_repeat('=', Helper::strlenWithoutDecoration($this->getFormatter(), $message))),
));
$this->newLine();
}




public function section($message)
{
$this->autoPrependBlock();
$this->writeln(array(
sprintf('<comment>%s</>', $message),
sprintf('<comment>%s</>', str_repeat('-', Helper::strlenWithoutDecoration($this->getFormatter(), $message))),
));
$this->newLine();
}




public function listing(array $elements)
{
$this->autoPrependText();
$elements = array_map(function ($element) {
return sprintf(' * %s', $element);
}, $elements);

$this->writeln($elements);
$this->newLine();
}




public function text($message)
{
$this->autoPrependText();

$messages = is_array($message) ? array_values($message) : array($message);
foreach ($messages as $message) {
$this->writeln(sprintf(' %s', $message));
}
}




public function comment($message)
{
$this->autoPrependText();

$messages = is_array($message) ? array_values($message) : array($message);
foreach ($messages as $message) {
$this->writeln(sprintf(' // %s', $message));
}
}




public function success($message)
{
$this->block($message, 'OK', 'fg=black;bg=green', ' ', true);
}




public function error($message)
{
$this->block($message, 'ERROR', 'fg=white;bg=red', ' ', true);
}




public function warning($message)
{
$this->block($message, 'WARNING', 'fg=white;bg=red', ' ', true);
}




public function note($message)
{
$this->block($message, 'NOTE', 'fg=yellow', ' ! ');
}




public function caution($message)
{
$this->block($message, 'CAUTION', 'fg=white;bg=red', ' ! ', true);
}




public function table(array $headers, array $rows)
{
$headers = array_map(function ($value) { return sprintf('<info>%s</>', $value); }, $headers);

$table = new Table($this);
$table->setHeaders($headers);
$table->setRows($rows);
$table->setStyle('symfony-style-guide');

$table->render();
$this->newLine();
}




public function ask($question, $default = null, $validator = null)
{
$question = new Question($question, $default);
$question->setValidator($validator);

return $this->askQuestion($question);
}




public function askHidden($question, $validator = null)
{
$question = new Question($question);

$question->setHidden(true);
$question->setValidator($validator);

return $this->askQuestion($question);
}




public function confirm($question, $default = true)
{
return $this->askQuestion(new ConfirmationQuestion($question, $default));
}




public function choice($question, array $choices, $default = null)
{
if (null !== $default) {
$values = array_flip($choices);
$default = $values[$default];
}

return $this->askQuestion(new ChoiceQuestion($question, $choices, $default));
}




public function progressStart($max = 0)
{
$this->progressBar = $this->createProgressBar($max);
$this->progressBar->start();
}




public function progressAdvance($step = 1)
{
$this->getProgressBar()->advance($step);
}




public function progressFinish()
{
$this->getProgressBar()->finish();
$this->newLine(2);
$this->progressBar = null;
}




public function createProgressBar($max = 0)
{
$progressBar = parent::createProgressBar($max);

if ('\\' !== DIRECTORY_SEPARATOR) {
$progressBar->setEmptyBarCharacter('░'); 
 $progressBar->setProgressCharacter('');
$progressBar->setBarCharacter('▓'); 
 }

return $progressBar;
}






public function askQuestion(Question $question)
{
if ($this->input->isInteractive()) {
$this->autoPrependBlock();
}

if (!$this->questionHelper) {
$this->questionHelper = new SymfonyQuestionHelper();
}

$answer = $this->questionHelper->ask($this->input, $this, $question);

if ($this->input->isInteractive()) {
$this->newLine();
$this->bufferedOutput->write("\n");
}

return $answer;
}




public function writeln($messages, $type = self::OUTPUT_NORMAL)
{
parent::writeln($messages, $type);
$this->bufferedOutput->writeln($this->reduceBuffer($messages), $type);
}




public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL)
{
parent::write($messages, $newline, $type);
$this->bufferedOutput->write($this->reduceBuffer($messages), $newline, $type);
}




public function newLine($count = 1)
{
parent::newLine($count);
$this->bufferedOutput->write(str_repeat("\n", $count));
}




private function getProgressBar()
{
if (!$this->progressBar) {
throw new RuntimeException('The ProgressBar is not started.');
}

return $this->progressBar;
}

private function getTerminalWidth()
{
$application = new Application();
$dimensions = $application->getTerminalDimensions();

return $dimensions[0] ?: self::MAX_LINE_LENGTH;
}

private function autoPrependBlock()
{
$chars = substr(str_replace(PHP_EOL, "\n", $this->bufferedOutput->fetch()), -2);

if (!isset($chars[0])) {
return $this->newLine(); 
 }

 $this->newLine(2 - substr_count($chars, "\n"));
}

private function autoPrependText()
{
$fetched = $this->bufferedOutput->fetch();

 if ("\n" !== substr($fetched, -1)) {
$this->newLine();
}
}

private function reduceBuffer($messages)
{

 
 return array_map(function ($value) {
return substr($value, -4);
}, array_merge(array($this->bufferedOutput->fetch()), (array) $messages));
}
}
