<?php










namespace Composer\Semver\Constraint;




class MultiConstraint implements ConstraintInterface
{

protected $constraints;


protected $prettyString;


protected $conjunctive;





public function __construct(array $constraints, $conjunctive = true)
{
$this->constraints = $constraints;
$this->conjunctive = $conjunctive;
}






public function matches(ConstraintInterface $provider)
{
if (false === $this->conjunctive) {
foreach ($this->constraints as $constraint) {
if ($constraint->matches($provider)) {
return true;
}
}

return false;
}

foreach ($this->constraints as $constraint) {
if (!$constraint->matches($provider)) {
return false;
}
}

return true;
}




public function setPrettyString($prettyString)
{
$this->prettyString = $prettyString;
}




public function getPrettyString()
{
if ($this->prettyString) {
return $this->prettyString;
}

return $this->__toString();
}




public function __toString()
{
$constraints = array();
foreach ($this->constraints as $constraint) {
$constraints[] = (string) $constraint;
}

return '[' . implode($this->conjunctive ? ' ' : ' || ', $constraints) . ']';
}
}
