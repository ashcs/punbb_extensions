<?php








namespace JsonSchema\Constraints;

use JsonSchema\Exception\InvalidArgumentException;
use JsonSchema\Uri\UriResolver;







class UndefinedConstraint extends Constraint
{



public function check($value, $schema = null, $path = null, $i = null)
{
if (is_null($schema)) {
return;
}

if (!is_object($schema)) {
throw new InvalidArgumentException(
'Given schema must be an object in ' . $path
. ' but is a ' . gettype($schema)
);
}

$i = is_null($i) ? "" : $i;
$path = $this->incrementPath($path, $i);


 $this->validateCommonProperties($value, $schema, $path);


 $this->validateOfProperties($value, $schema, $path);


 $this->validateTypes($value, $schema, $path, $i);
}









public function validateTypes($value, $schema = null, $path = null, $i = null)
{

 if (is_array($value)) {
$this->checkArray($value, $schema, $path, $i);
}


 if (is_object($value) && (isset($schema->properties) || isset($schema->patternProperties) || isset($schema->additionalProperties))) {
$this->checkObject(
$value,
isset($schema->properties) ? $schema->properties : null,
$path,
isset($schema->additionalProperties) ? $schema->additionalProperties : null,
isset($schema->patternProperties) ? $schema->patternProperties : null
);
}


 if (is_string($value)) {
$this->checkString($value, $schema, $path, $i);
}


 if (is_numeric($value)) {
$this->checkNumber($value, $schema, $path, $i);
}


 if (isset($schema->enum)) {
$this->checkEnum($value, $schema, $path, $i);
}
}









protected function validateCommonProperties($value, $schema = null, $path = null, $i = "")
{

 if (isset($schema->extends)) {
if (is_string($schema->extends)) {
$schema->extends = $this->validateUri($schema, $schema->extends);
}
if (is_array($schema->extends)) {
foreach ($schema->extends as $extends) {
$this->checkUndefined($value, $extends, $path, $i);
}
} else {
$this->checkUndefined($value, $schema->extends, $path, $i);
}
}


 if (is_object($value)) {
if (!($value instanceof UndefinedConstraint) && isset($schema->required) && is_array($schema->required) ) {

 foreach ($schema->required as $required) {
if (!property_exists($value, $required)) {
$this->addError((!$path) ? $required : "$path.$required", "The property " . $required . " is required", 'required');
}
}
} else if (isset($schema->required) && !is_array($schema->required)) {

 if ( $schema->required && $value instanceof UndefinedConstraint) {
$this->addError($path, "Is missing and it is required", 'required');
}
}
}


 if (!($value instanceof UndefinedConstraint)) {
$this->checkType($value, $schema, $path);
}


 if (isset($schema->disallow)) {
$initErrors = $this->getErrors();

$typeSchema = new \stdClass();
$typeSchema->type = $schema->disallow;
$this->checkType($value, $typeSchema, $path);


 if (count($this->getErrors()) == count($initErrors)) {
$this->addError($path, "Disallowed value was matched", 'disallow');
} else {
$this->errors = $initErrors;
}
}

if (isset($schema->not)) {
$initErrors = $this->getErrors();
$this->checkUndefined($value, $schema->not, $path, $i);


 if (count($this->getErrors()) == count($initErrors)) {
$this->addError($path, "Matched a schema which it should not", 'not');
} else {
$this->errors = $initErrors;
}
}


 if (is_object($value)) {
if (isset($schema->minProperties)) {
if (count(get_object_vars($value)) < $schema->minProperties) {
$this->addError($path, "Must contain a minimum of " . $schema->minProperties . " properties", 'minProperties', array('minProperties' => $schema->minProperties,));
}
}
if (isset($schema->maxProperties)) {
if (count(get_object_vars($value)) > $schema->maxProperties) {
$this->addError($path, "Must contain no more than " . $schema->maxProperties . " properties", 'maxProperties', array('maxProperties' => $schema->maxProperties,));
}
}
}


 if (is_object($value) && isset($schema->dependencies)) {
$this->validateDependencies($value, $schema->dependencies, $path);
}
}









protected function validateOfProperties($value, $schema, $path, $i = "")
{

 if ($value instanceof UndefinedConstraint) {
return;
}

if (isset($schema->allOf)) {
$isValid = true;
foreach ($schema->allOf as $allOf) {
$initErrors = $this->getErrors();
$this->checkUndefined($value, $allOf, $path, $i);
$isValid = $isValid && (count($this->getErrors()) == count($initErrors));
}
if (!$isValid) {
$this->addError($path, "Failed to match all schemas", 'allOf');
}
}

if (isset($schema->anyOf)) {
$isValid = false;
$startErrors = $this->getErrors();
foreach ($schema->anyOf as $anyOf) {
$initErrors = $this->getErrors();
$this->checkUndefined($value, $anyOf, $path, $i);
if ($isValid = (count($this->getErrors()) == count($initErrors))) {
break;
}
}
if (!$isValid) {
$this->addError($path, "Failed to match at least one schema", 'anyOf');
} else {
$this->errors = $startErrors;
}
}

if (isset($schema->oneOf)) {
$allErrors = array();
$matchedSchemas = 0;
$startErrors = $this->getErrors();
foreach ($schema->oneOf as $oneOf) {
$this->errors = array();
$this->checkUndefined($value, $oneOf, $path, $i);
if (count($this->getErrors()) == 0) {
$matchedSchemas++;
}
$allErrors = array_merge($allErrors, array_values($this->getErrors()));
}
if ($matchedSchemas !== 1) {
$this->addErrors(
array_merge(
$allErrors,
array(array(
'property' => $path,
'message' => "Failed to match exactly one schema",
'constraint' => 'oneOf',
),),
$startErrors
)
);
} else {
$this->errors = $startErrors;
}
}
}









protected function validateDependencies($value, $dependencies, $path, $i = "")
{
foreach ($dependencies as $key => $dependency) {
if (property_exists($value, $key)) {
if (is_string($dependency)) {

 if (!property_exists($value, $dependency)) {
$this->addError($path, "$key depends on $dependency and $dependency is missing", 'dependencies');
}
} else if (is_array($dependency)) {

 foreach ($dependency as $d) {
if (!property_exists($value, $d)) {
$this->addError($path, "$key depends on $d and $d is missing", 'dependencies');
}
}
} else if (is_object($dependency)) {

 $this->checkUndefined($value, $dependency, $path, $i);
}
}
}
}

protected function validateUri($schema, $schemaUri = null)
{
$resolver = new UriResolver();
$retriever = $this->getUriRetriever();

$jsonSchema = null;
if ($resolver->isValid($schemaUri)) {
$schemaId = property_exists($schema, 'id') ? $schema->id : null;
$jsonSchema = $retriever->retrieve($schemaId, $schemaUri);
}

return $jsonSchema;
}
}
