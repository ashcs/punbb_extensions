<?php








namespace JsonSchema\Constraints;







class ObjectConstraint extends Constraint
{



function check($element, $definition = null, $path = null, $additionalProp = null, $patternProperties = null)
{
if ($element instanceof UndefinedConstraint) {
return;
}

$matches = array();
if ($patternProperties) {
$matches = $this->validatePatternProperties($element, $path, $patternProperties);
}

if ($definition) {

 $this->validateDefinition($element, $definition, $path);
}


 $this->validateElement($element, $matches, $definition, $path, $additionalProp);
}

public function validatePatternProperties($element, $path, $patternProperties)
{
$try = array('/','#','+','~','%');
$matches = array();
foreach ($patternProperties as $pregex => $schema) {
$delimiter = '/';

 foreach ($try as $delimiter) {
if (strpos($pregex, $delimiter) === false) { 
 break;
}
}


 if (@preg_match($delimiter. $pregex . $delimiter, '') === false) {
$this->addError($path, 'The pattern "' . $pregex . '" is invalid', 'pregex', array('pregex' => $pregex,));
continue;
}
foreach ($element as $i => $value) {
if (preg_match($delimiter . $pregex . $delimiter, $i)) {
$matches[] = $i;
$this->checkUndefined($value, $schema ? : new \stdClass(), $path, $i);
}
}
}
return $matches;
}










public function validateElement($element, $matches, $objectDefinition = null, $path = null, $additionalProp = null)
{
foreach ($element as $i => $value) {

$property = $this->getProperty($element, $i, new UndefinedConstraint());
$definition = $this->getProperty($objectDefinition, $i);


 if (!in_array($i, $matches) && $additionalProp === false && $this->inlineSchemaProperty !== $i && !$definition) {
$this->addError($path, "The property " . $i . " is not defined and the definition does not allow additional properties", 'additionalProp');
}


 if (!in_array($i, $matches) && $additionalProp && !$definition) {
if ($additionalProp === true) {
$this->checkUndefined($value, null, $path, $i);
} else {
$this->checkUndefined($value, $additionalProp, $path, $i);
}
}


 $require = $this->getProperty($definition, 'requires');
if ($require && !$this->getProperty($element, $require)) {
$this->addError($path, "The presence of the property " . $i . " requires that " . $require . " also be present", 'requires');
}

if (!$definition) {

 $this->checkUndefined($value, new \stdClass(), $path, $i);
}
}
}








public function validateDefinition($element, $objectDefinition = null, $path = null)
{
foreach ($objectDefinition as $i => $value) {
$property = $this->getProperty($element, $i, new UndefinedConstraint());
$definition = $this->getProperty($objectDefinition, $i);
$this->checkUndefined($property, $definition, $path, $i);
}
}










protected function getProperty($element, $property, $fallback = null)
{
if (is_array($element) ) {
return array_key_exists($property, $element) ? $element[$property] : $fallback;
} elseif (is_object($element)) {
return property_exists($element, $property) ? $element->$property : $fallback;
}

return $fallback;
}
}
