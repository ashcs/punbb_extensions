<?php








namespace JsonSchema\Constraints;

use JsonSchema\Uri\UriRetriever;







abstract class Constraint implements ConstraintInterface
{
protected $checkMode = self::CHECK_MODE_NORMAL;
protected $uriRetriever;
protected $errors = array();
protected $inlineSchemaProperty = '$schema';

const CHECK_MODE_NORMAL = 1;
const CHECK_MODE_TYPE_CAST = 2;




private $factory;






public function __construct($checkMode = self::CHECK_MODE_NORMAL, UriRetriever $uriRetriever = null, Factory $factory = null)
{
$this->checkMode = $checkMode;
$this->uriRetriever = $uriRetriever;
$this->factory = $factory;
}




public function getUriRetriever()
{
if (is_null($this->uriRetriever))
{
$this->setUriRetriever(new UriRetriever);
}

return $this->uriRetriever;
}




public function getFactory()
{
if (!$this->factory) {
$this->factory = new Factory($this->getUriRetriever());
}

return $this->factory;
}




public function setUriRetriever(UriRetriever $uriRetriever)
{
$this->uriRetriever = $uriRetriever;
}




public function addError($path, $message, $constraint='', array $more=null)
{
$error = array(
'property' => $path,
'message' => $message,
'constraint' => $constraint,
);

if (is_array($more) && count($more) > 0)
{
$error += $more;
}

$this->errors[] = $error;
}




public function addErrors(array $errors)
{
$this->errors = array_merge($this->errors, $errors);
}




public function getErrors()
{
return $this->errors;
}




public function isValid()
{
return !$this->getErrors();
}





public function reset()
{
$this->errors = array();
}









protected function incrementPath($path, $i)
{
if ($path !== '') {
if (is_int($i)) {
$path .= '[' . $i . ']';
} elseif ($i == '') {
$path .= '';
} else {
$path .= '.' . $i;
}
} else {
$path = $i;
}

return $path;
}









protected function checkArray($value, $schema = null, $path = null, $i = null)
{
$validator = $this->getFactory()->createInstanceFor('collection');
$validator->check($value, $schema, $path, $i);

$this->addErrors($validator->getErrors());
}










protected function checkObject($value, $schema = null, $path = null, $i = null, $patternProperties = null)
{
$validator = $this->getFactory()->createInstanceFor('object');
$validator->check($value, $schema, $path, $i, $patternProperties);

$this->addErrors($validator->getErrors());
}









protected function checkType($value, $schema = null, $path = null, $i = null)
{
$validator = $this->getFactory()->createInstanceFor('type');
$validator->check($value, $schema, $path, $i);

$this->addErrors($validator->getErrors());
}









protected function checkUndefined($value, $schema = null, $path = null, $i = null)
{
$validator = $this->getFactory()->createInstanceFor('undefined');
$validator->check($value, $schema, $path, $i);

$this->addErrors($validator->getErrors());
}









protected function checkString($value, $schema = null, $path = null, $i = null)
{
$validator = $this->getFactory()->createInstanceFor('string');
$validator->check($value, $schema, $path, $i);

$this->addErrors($validator->getErrors());
}









protected function checkNumber($value, $schema = null, $path = null, $i = null)
{
$validator = $this->getFactory()->createInstanceFor('number');
$validator->check($value, $schema, $path, $i);

$this->addErrors($validator->getErrors());
}









protected function checkEnum($value, $schema = null, $path = null, $i = null)
{
$validator = $this->getFactory()->createInstanceFor('enum');
$validator->check($value, $schema, $path, $i);

$this->addErrors($validator->getErrors());
}

protected function checkFormat($value, $schema = null, $path = null, $i = null)
{
$validator = $this->getFactory()->createInstanceFor('format');
$validator->check($value, $schema, $path, $i);

$this->addErrors($validator->getErrors());
}





protected function retrieveUri($uri)
{
if (null === $this->uriRetriever) {
$this->setUriRetriever(new UriRetriever);
}
$jsonSchema = $this->uriRetriever->retrieve($uri);

 return $jsonSchema;
}
}
