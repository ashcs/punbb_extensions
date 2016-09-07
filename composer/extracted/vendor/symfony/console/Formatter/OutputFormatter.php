<?php










namespace Symfony\Component\Console\Formatter;

use Symfony\Component\Console\Exception\InvalidArgumentException;






class OutputFormatter implements OutputFormatterInterface
{
private $decorated;
private $styles = array();
private $styleStack;








public static function escape($text)
{
return preg_replace('/([^\\\\]?)</', '$1\\<', $text);
}







public function __construct($decorated = false, array $styles = array())
{
$this->decorated = (bool) $decorated;

$this->setStyle('error', new OutputFormatterStyle('white', 'red'));
$this->setStyle('info', new OutputFormatterStyle('green'));
$this->setStyle('comment', new OutputFormatterStyle('yellow'));
$this->setStyle('question', new OutputFormatterStyle('black', 'cyan'));

foreach ($styles as $name => $style) {
$this->setStyle($name, $style);
}

$this->styleStack = new OutputFormatterStyleStack();
}






public function setDecorated($decorated)
{
$this->decorated = (bool) $decorated;
}






public function isDecorated()
{
return $this->decorated;
}







public function setStyle($name, OutputFormatterStyleInterface $style)
{
$this->styles[strtolower($name)] = $style;
}








public function hasStyle($name)
{
return isset($this->styles[strtolower($name)]);
}










public function getStyle($name)
{
if (!$this->hasStyle($name)) {
throw new InvalidArgumentException(sprintf('Undefined style: %s', $name));
}

return $this->styles[strtolower($name)];
}








public function format($message)
{
$message = (string) $message;
$offset = 0;
$output = '';
$tagRegex = '[a-z][a-z0-9_=;-]*';
preg_match_all("#<(($tagRegex) | /($tagRegex)?)>#ix", $message, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $i => $match) {
$pos = $match[1];
$text = $match[0];

if (0 != $pos && '\\' == $message[$pos - 1]) {
continue;
}


 $output .= $this->applyCurrentStyle(substr($message, $offset, $pos - $offset));
$offset = $pos + strlen($text);


 if ($open = '/' != $text[1]) {
$tag = $matches[1][$i][0];
} else {
$tag = isset($matches[3][$i][0]) ? $matches[3][$i][0] : '';
}

if (!$open && !$tag) {

 $this->styleStack->pop();
} elseif (false === $style = $this->createStyleFromString(strtolower($tag))) {
$output .= $this->applyCurrentStyle($text);
} elseif ($open) {
$this->styleStack->push($style);
} else {
$this->styleStack->pop($style);
}
}

$output .= $this->applyCurrentStyle(substr($message, $offset));

return str_replace('\\<', '<', $output);
}




public function getStyleStack()
{
return $this->styleStack;
}








private function createStyleFromString($string)
{
if (isset($this->styles[$string])) {
return $this->styles[$string];
}

if (!preg_match_all('/([^=]+)=([^;]+)(;|$)/', strtolower($string), $matches, PREG_SET_ORDER)) {
return false;
}

$style = new OutputFormatterStyle();
foreach ($matches as $match) {
array_shift($match);

if ('fg' == $match[0]) {
$style->setForeground($match[1]);
} elseif ('bg' == $match[0]) {
$style->setBackground($match[1]);
} else {
try {
$style->setOption($match[1]);
} catch (\InvalidArgumentException $e) {
return false;
}
}
}

return $style;
}








private function applyCurrentStyle($text)
{
return $this->isDecorated() && strlen($text) > 0 ? $this->styleStack->getCurrent()->apply($text) : $text;
}
}
