<?php










namespace Symfony\Component\Console\Helper;

use Symfony\Component\Console\Output\OutputInterface;




class ProgressIndicator
{
private $output;
private $startTime;
private $format;
private $message;
private $indicatorValues;
private $indicatorCurrent;
private $indicatorChangeInterval;
private $indicatorUpdateTime;
private $lastMessagesLength;
private $started = false;

private static $formatters;
private static $formats;







public function __construct(OutputInterface $output, $format = null, $indicatorChangeInterval = 100, $indicatorValues = null)
{
$this->output = $output;

if (null === $format) {
$format = $this->determineBestFormat();
}

if (null === $indicatorValues) {
$indicatorValues = array('-', '\\', '|', '/');
}

$indicatorValues = array_values($indicatorValues);

if (2 > count($indicatorValues)) {
throw new \InvalidArgumentException('Must have at least 2 indicator value characters.');
}

$this->format = self::getFormatDefinition($format);
$this->indicatorChangeInterval = $indicatorChangeInterval;
$this->indicatorValues = $indicatorValues;
$this->startTime = time();
}






public function setMessage($message)
{
$this->message = $message;

$this->display();
}








public function getMessage()
{
return $this->message;
}








public function getStartTime()
{
return $this->startTime;
}








public function getCurrentValue()
{
return $this->indicatorValues[$this->indicatorCurrent % count($this->indicatorValues)];
}






public function start($message)
{
if ($this->started) {
throw new \LogicException('Progress indicator already started.');
}

$this->message = $message;
$this->started = true;
$this->lastMessagesLength = 0;
$this->startTime = time();
$this->indicatorUpdateTime = $this->getCurrentTimeInMilliseconds() + $this->indicatorChangeInterval;
$this->indicatorCurrent = 0;

$this->display();
}




public function advance()
{
if (!$this->started) {
throw new \LogicException('Progress indicator has not yet been started.');
}

if (!$this->output->isDecorated()) {
return;
}

$currentTime = $this->getCurrentTimeInMilliseconds();

if ($currentTime < $this->indicatorUpdateTime) {
return;
}

$this->indicatorUpdateTime = $currentTime + $this->indicatorChangeInterval;
++$this->indicatorCurrent;

$this->display();
}






public function finish($message)
{
if (!$this->started) {
throw new \LogicException('Progress indicator has not yet been started.');
}

$this->message = $message;
$this->display();
$this->output->writeln('');
$this->started = false;
}








public static function getFormatDefinition($name)
{
if (!self::$formats) {
self::$formats = self::initFormats();
}

return isset(self::$formats[$name]) ? self::$formats[$name] : null;
}









public static function setPlaceholderFormatterDefinition($name, $callable)
{
if (!self::$formatters) {
self::$formatters = self::initPlaceholderFormatters();
}

self::$formatters[$name] = $callable;
}








public static function getPlaceholderFormatterDefinition($name)
{
if (!self::$formatters) {
self::$formatters = self::initPlaceholderFormatters();
}

return isset(self::$formatters[$name]) ? self::$formatters[$name] : null;
}

private function display()
{
if (OutputInterface::VERBOSITY_QUIET === $this->output->getVerbosity()) {
return;
}

$self = $this;

$this->overwrite(preg_replace_callback("{%([a-z\-_]+)(?:\:([^%]+))?%}i", function ($matches) use ($self) {
if ($formatter = $self::getPlaceholderFormatterDefinition($matches[1])) {
return call_user_func($formatter, $self);
}

return $matches[0];
}, $this->format));
}

private function determineBestFormat()
{
switch ($this->output->getVerbosity()) {

 case OutputInterface::VERBOSITY_VERBOSE:
return $this->output->isDecorated() ? 'verbose' : 'verbose_no_ansi';
case OutputInterface::VERBOSITY_VERY_VERBOSE:
case OutputInterface::VERBOSITY_DEBUG:
return $this->output->isDecorated() ? 'very_verbose' : 'very_verbose_no_ansi';
default:
return $this->output->isDecorated() ? 'normal' : 'normal_no_ansi';
}
}






private function overwrite($message)
{

 if (null !== $this->lastMessagesLength) {
if ($this->lastMessagesLength > Helper::strlenWithoutDecoration($this->output->getFormatter(), $message)) {
$message = str_pad($message, $this->lastMessagesLength, "\x20", STR_PAD_RIGHT);
}
}

if ($this->output->isDecorated()) {
$this->output->write("\x0D");
$this->output->write($message);
} else {
$this->output->writeln($message);
}

$this->lastMessagesLength = 0;

$len = Helper::strlenWithoutDecoration($this->output->getFormatter(), $message);

if ($len > $this->lastMessagesLength) {
$this->lastMessagesLength = $len;
}
}

private function getCurrentTimeInMilliseconds()
{
return round(microtime(true) * 1000);
}

private static function initPlaceholderFormatters()
{
return array(
'indicator' => function (ProgressIndicator $indicator) {
return $indicator->getCurrentValue();
},
'message' => function (ProgressIndicator $indicator) {
return $indicator->getMessage();
},
'elapsed' => function (ProgressIndicator $indicator) {
return Helper::formatTime(time() - $indicator->getStartTime());
},
'memory' => function () {
return Helper::formatMemory(memory_get_usage(true));
},
);
}

private static function initFormats()
{
return array(
'normal' => ' %indicator% %message%',
'normal_no_ansi' => ' %message%',

'verbose' => ' %indicator% %message% (%elapsed:6s%)',
'verbose_no_ansi' => ' %message% (%elapsed:6s%)',

'very_verbose' => ' %indicator% %message% (%elapsed:6s%, %memory:6s%)',
'very_verbose_no_ansi' => ' %message% (%elapsed:6s%, %memory:6s%)',
);
}
}
