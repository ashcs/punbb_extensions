<?php










namespace Seld\JsonLint;

class DuplicateKeyException extends ParsingException
{
public function __construct($message, $key, array $details = array())
{
$details['key'] = $key;
parent::__construct($message, $details);
}

public function getKey()
{
return $this->details['key'];
}
}
