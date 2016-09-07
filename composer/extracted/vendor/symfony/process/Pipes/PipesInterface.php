<?php










namespace Symfony\Component\Process\Pipes;








interface PipesInterface
{
const CHUNK_SIZE = 16384;






public function getDescriptors();






public function getFiles();









public function readAndWrite($blocking, $close = false);






public function areOpen();




public function close();
}
