<?php








namespace JsonSchema\Constraints;

use JsonSchema\Exception\InvalidArgumentException;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;




class Factory
{



protected $uriRetriever;




public function __construct(UriRetriever $uriRetriever = null)
{
if (!$uriRetriever) {
$uriRetriever = new UriRetriever();
}

$this->uriRetriever = $uriRetriever;
}




public function getUriRetriever()
{
return $this->uriRetriever;
}








public function createInstanceFor($constraintName)
{
switch ($constraintName) {
case 'array':
case 'collection':
return new CollectionConstraint(Constraint::CHECK_MODE_NORMAL, $this->uriRetriever, $this);
case 'object':
return new ObjectConstraint(Constraint::CHECK_MODE_NORMAL, $this->uriRetriever, $this);
case 'type':
return new TypeConstraint(Constraint::CHECK_MODE_NORMAL, $this->uriRetriever, $this);
case 'undefined':
return new UndefinedConstraint(Constraint::CHECK_MODE_NORMAL, $this->uriRetriever, $this);
case 'string':
return new StringConstraint(Constraint::CHECK_MODE_NORMAL, $this->uriRetriever, $this);
case 'number':
return new NumberConstraint(Constraint::CHECK_MODE_NORMAL, $this->uriRetriever, $this);
case 'enum':
return new EnumConstraint(Constraint::CHECK_MODE_NORMAL, $this->uriRetriever, $this);
case 'format':
return new FormatConstraint(Constraint::CHECK_MODE_NORMAL, $this->uriRetriever, $this);
case 'schema':
return new SchemaConstraint(Constraint::CHECK_MODE_NORMAL, $this->uriRetriever, $this);
case 'validator':
return new Validator(Constraint::CHECK_MODE_NORMAL, $this->uriRetriever, $this);
}

throw new InvalidArgumentException('Unknown constraint ' . $constraintName);
}
}
