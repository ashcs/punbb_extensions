<?php











namespace Composer\Json;









class JsonFormatter
{












public static function format($json, $unescapeUnicode, $unescapeSlashes)
{
$result = '';
$pos = 0;
$strLen = strlen($json);
$indentStr = '    ';
$newLine = "\n";
$outOfQuotes = true;
$buffer = '';
$noescape = true;

for ($i = 0; $i < $strLen; $i++) {

 $char = substr($json, $i, 1);


 if ('"' === $char && $noescape) {
$outOfQuotes = !$outOfQuotes;
}

if (!$outOfQuotes) {
$buffer .= $char;
$noescape = '\\' === $char ? !$noescape : true;
continue;
} elseif ('' !== $buffer) {
if ($unescapeSlashes) {
$buffer = str_replace('\\/', '/', $buffer);
}

if ($unescapeUnicode && function_exists('mb_convert_encoding')) {

 $buffer = preg_replace_callback('/(\\\\+)u([0-9a-f]{4})/i', function ($match) {
$l = strlen($match[1]);

if ($l % 2) {
return str_repeat('\\', $l - 1) . mb_convert_encoding(
pack('H*', $match[2]),
'UTF-8',
'UCS-2BE'
);
}

return $match[0];
}, $buffer);
}

$result .= $buffer.$char;
$buffer = '';
continue;
}

if (':' === $char) {

 $char .= ' ';
} elseif (('}' === $char || ']' === $char)) {
$pos--;
$prevChar = substr($json, $i - 1, 1);

if ('{' !== $prevChar && '[' !== $prevChar) {

 
 $result .= $newLine;
for ($j = 0; $j < $pos; $j++) {
$result .= $indentStr;
}
} else {

 $result = rtrim($result);
}
}

$result .= $char;


 
 if (',' === $char || '{' === $char || '[' === $char) {
$result .= $newLine;

if ('{' === $char || '[' === $char) {
$pos++;
}

for ($j = 0; $j < $pos; $j++) {
$result .= $indentStr;
}
}
}

return $result;
}
}
