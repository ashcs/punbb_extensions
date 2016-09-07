<?php








namespace JsonSchema\Uri\Retrievers;

use JsonSchema\Exception\ResourceNotFoundException;
use JsonSchema\Validator;






class FileGetContents extends AbstractRetriever
{
protected $messageBody;





public function retrieve($uri)
{
$context = stream_context_create(array(
'http' => array(
'method' => 'GET',
'header' => "Accept: " . Validator::SCHEMA_MEDIA_TYPE
)));

set_error_handler(function() use ($uri) {
throw new ResourceNotFoundException('JSON schema not found at ' . $uri);
});
$response = file_get_contents($uri);
restore_error_handler();

if (false === $response) {
throw new ResourceNotFoundException('JSON schema not found at ' . $uri);
}
if ($response == ''
&& substr($uri, 0, 7) == 'file://' && substr($uri, -1) == '/'
) {
throw new ResourceNotFoundException('JSON schema not found at ' . $uri);
}

$this->messageBody = $response;
if (! empty($http_response_header)) {
$this->fetchContentType($http_response_header);
} else {

 $this->contentType = null;
}

return $this->messageBody;
}





private function fetchContentType(array $headers)
{
foreach ($headers as $header) {
if ($this->contentType = self::getContentTypeMatchInHeader($header)) {
return true;
}
}

return false;
}





protected static function getContentTypeMatchInHeader($header)
{
if (0 < preg_match("/Content-Type:(\V*)/ims", $header, $match)) {
return trim($match[1]);
}
}
}
