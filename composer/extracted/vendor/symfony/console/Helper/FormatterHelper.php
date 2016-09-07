<?php










namespace Symfony\Component\Console\Helper;

use Symfony\Component\Console\Formatter\OutputFormatter;






class FormatterHelper extends Helper
{









public function formatSection($section, $message, $style = 'info')
{
return sprintf('<%s>[%s]</%s> %s', $style, $section, $style, $message);
}










public function formatBlock($messages, $style, $large = false)
{
if (!is_array($messages)) {
$messages = array($messages);
}

$len = 0;
$lines = array();
foreach ($messages as $message) {
$message = OutputFormatter::escape($message);
$lines[] = sprintf($large ? '  %s  ' : ' %s ', $message);
$len = max($this->strlen($message) + ($large ? 4 : 2), $len);
}

$messages = $large ? array(str_repeat(' ', $len)) : array();
for ($i = 0; isset($lines[$i]); ++$i) {
$messages[] = $lines[$i].str_repeat(' ', $len - $this->strlen($lines[$i]));
}
if ($large) {
$messages[] = str_repeat(' ', $len);
}

for ($i = 0; isset($messages[$i]); ++$i) {
$messages[$i] = sprintf('<%s>%s</%s>', $style, $messages[$i], $style);
}

return implode("\n", $messages);
}




public function getName()
{
return 'formatter';
}
}
