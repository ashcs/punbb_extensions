<?php








namespace JsonSchema\Constraints;

use JsonSchema\Exception\InvalidArgumentException;
use UnexpectedValueException as StandardUnexpectedValueException;







class TypeConstraint extends Constraint
{



static $wording = array(
'integer' => 'an integer',
'number' => 'a number',
'boolean' => 'a boolean',
'object' => 'an object',
'array' => 'an array',
'string' => 'a string',
'null' => 'a null',
'any' => NULL, 
 0 => NULL, 
 );




public function check($value = null, $schema = null, $path = null, $i = null)
{
$type = isset($schema->type) ? $schema->type : null;
$isValid = true;

if (is_array($type)) {

 $validatedOneType = false;
$errors = array();
foreach ($type as $tp) {
$validator = new TypeConstraint($this->checkMode);
$subSchema = new \stdClass();
$subSchema->type = $tp;
$validator->check($value, $subSchema, $path, null);
$error = $validator->getErrors();

if (!count($error)) {
$validatedOneType = true;
break;
}

$errors = $error;
}

if (!$validatedOneType) {
$this->addErrors($errors);

return;
}
} elseif (is_object($type)) {
$this->checkUndefined($value, $type, $path);
} else {
$isValid = $this->validateType($value, $type);
}

if ($isValid === false) {
if (!isset(self::$wording[$type])) {
throw new StandardUnexpectedValueException(
sprintf(
"No wording for %s available, expected wordings are: [%s]",
var_export($type, true),
implode(', ', array_filter(self::$wording)))
);
}
$this->addError($path, ucwords(gettype($value)) . " value found, but " . self::$wording[$type] . " is required", 'type');
}
}











protected function validateType($value, $type)
{

 if (!$type) {
return true;
}

if ('integer' === $type) {
return is_int($value);
}

if ('number' === $type) {
return is_numeric($value) && !is_string($value);
}

if ('boolean' === $type) {
return is_bool($value);
}

if ('object' === $type) {
return is_object($value);

 }

if ('array' === $type) {
return is_array($value);
}

if ('string' === $type) {
return is_string($value);
}

if ('email' === $type) {
return is_string($value);
}

if ('null' === $type) {
return is_null($value);
}

if ('any' === $type) {
return true;
}

throw new InvalidArgumentException((is_object($value) ? 'object' : $value) . ' is an invalid type for ' . $type);
}
}
