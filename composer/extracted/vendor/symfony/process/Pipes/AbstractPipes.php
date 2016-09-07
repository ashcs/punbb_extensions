<?php










namespace Symfony\Component\Process\Pipes;






abstract class AbstractPipes implements PipesInterface
{

public $pipes = array();


protected $inputBuffer = '';

protected $input;


private $blocked = true;




public function close()
{
foreach ($this->pipes as $pipe) {
fclose($pipe);
}
$this->pipes = array();
}






protected function hasSystemCallBeenInterrupted()
{
$lastError = error_get_last();


 return isset($lastError['message']) && false !== stripos($lastError['message'], 'interrupted system call');
}




protected function unblock()
{
if (!$this->blocked) {
return;
}

foreach ($this->pipes as $pipe) {
stream_set_blocking($pipe, 0);
}
if (null !== $this->input) {
stream_set_blocking($this->input, 0);
}

$this->blocked = false;
}
}
