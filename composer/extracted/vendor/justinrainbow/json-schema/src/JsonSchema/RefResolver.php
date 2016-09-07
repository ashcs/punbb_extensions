<?php








namespace JsonSchema;

use JsonSchema\Exception\JsonDecodingException;
use JsonSchema\Uri\Retrievers\UriRetrieverInterface;
use JsonSchema\Uri\UriRetriever;







class RefResolver
{







protected static $depth = 0;





public static $maxDepth = 7;




protected $uriRetriever = null;




protected $rootSchema = null;




public function __construct($retriever = null)
{
$this->uriRetriever = $retriever;
}








public function fetchRef($ref, $sourceUri)
{
$retriever = $this->getUriRetriever();
$jsonSchema = $retriever->retrieve($ref, $sourceUri);
$this->resolve($jsonSchema);

return $jsonSchema;
}







public function getUriRetriever()
{
if (is_null($this->uriRetriever)) {
$this->setUriRetriever(new UriRetriever);
}

return $this->uriRetriever;
}















public function resolve($schema, $sourceUri = null)
{
if (self::$depth > self::$maxDepth) {
self::$depth = 0;
throw new JsonDecodingException(JSON_ERROR_DEPTH);
}
++self::$depth;

if (! is_object($schema)) {
--self::$depth;
return;
}

if (null === $sourceUri && ! empty($schema->id)) {
$sourceUri = $schema->id;
}

if (null === $this->rootSchema) {
$this->rootSchema = $schema;
}


 $this->resolveRef($schema, $sourceUri);


 
 foreach (array('additionalItems', 'additionalProperties', 'extends', 'items') as $propertyName) {
$this->resolveProperty($schema, $propertyName, $sourceUri);
}


 
 
 foreach (array('disallow', 'extends', 'items', 'type', 'allOf', 'anyOf', 'oneOf') as $propertyName) {
$this->resolveArrayOfSchemas($schema, $propertyName, $sourceUri);
}


 foreach (array('dependencies', 'patternProperties', 'properties') as $propertyName) {
$this->resolveObjectOfSchemas($schema, $propertyName, $sourceUri);
}

--self::$depth;
}









public function resolveArrayOfSchemas($schema, $propertyName, $sourceUri)
{
if (! isset($schema->$propertyName) || ! is_array($schema->$propertyName)) {
return;
}

foreach ($schema->$propertyName as $possiblySchema) {
$this->resolve($possiblySchema, $sourceUri);
}
}









public function resolveObjectOfSchemas($schema, $propertyName, $sourceUri)
{
if (! isset($schema->$propertyName) || ! is_object($schema->$propertyName)) {
return;
}

foreach (get_object_vars($schema->$propertyName) as $possiblySchema) {
$this->resolve($possiblySchema, $sourceUri);
}
}









public function resolveProperty($schema, $propertyName, $sourceUri)
{
if (! isset($schema->$propertyName)) {
return;
}

$this->resolve($schema->$propertyName, $sourceUri);
}









public function resolveRef($schema, $sourceUri)
{
$ref = '$ref';

if (empty($schema->$ref)) {
return;
}

$splitRef = explode('#', $schema->$ref, 2);

$refDoc = $splitRef[0];
$refPath = null;
if (count($splitRef) === 2) {
$refPath = explode('/', $splitRef[1]);
array_shift($refPath);
}

if (empty($refDoc) && empty($refPath)) {

 return;
}

if (!empty($refDoc)) {
$refSchema = $this->fetchRef($refDoc, $sourceUri);
} else {
$refSchema = $this->rootSchema;
}

if (null !== $refPath) {
$refSchema = $this->resolveRefSegment($refSchema, $refPath);
}

unset($schema->$ref);


 foreach (get_object_vars($refSchema) as $prop => $value) {
$schema->$prop = $value;
}
}







public function setUriRetriever(UriRetriever $retriever)
{
$this->uriRetriever = $retriever;

return $this;
}

protected function resolveRefSegment($data, $pathParts)
{
foreach ($pathParts as $path) {
$path = strtr($path, array('~1' => '/', '~0' => '~', '%25' => '%'));

if (is_array($data)) {
$data = $data[$path];
} else {
$data = $data->{$path};
}
}

return $data;
}
}
