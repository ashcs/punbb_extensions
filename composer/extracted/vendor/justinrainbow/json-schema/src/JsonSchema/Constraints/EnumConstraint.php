<?php








namespace JsonSchema\Constraints;







class EnumConstraint extends Constraint
{



public function check($element, $schema = null, $path = null, $i = null)
{

 if ($element instanceof UndefinedConstraint && (!isset($schema->required) || !$schema->required)) {
return;
}

foreach ($schema->enum as $enum) {
$type = gettype($element);
if ($type === gettype($enum)) {
if ($type == "object") {
if ($element == $enum)
return;
} else {
if ($element === $enum)
return;

}
}
}

$this->addError($path, "Does not have a value in the enumeration " . print_r($schema->enum, true), 'enum', array('enum' => $schema->enum,));
}
}
