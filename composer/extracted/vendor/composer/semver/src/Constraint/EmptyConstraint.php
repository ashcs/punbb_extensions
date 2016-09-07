<?php










namespace Composer\Semver\Constraint;




class EmptyConstraint implements ConstraintInterface
{

protected $prettyString;






public function matches(ConstraintInterface $provider)
{
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
return '[]';
}
}
