<?php








namespace JsonSchema\Constraints;






interface ConstraintInterface
{





public function getErrors();






public function addErrors(array $errors);









public function addError($path, $message, $constraint='', array $more=null);






public function isValid();










public function check($value, $schema = null, $path = null, $i = null);
}