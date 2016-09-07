<?php











namespace Composer\Util;




class Keys
{
public static function fingerprint($path)
{
$hash = strtoupper(hash('sha256', preg_replace('{\s}', '', file_get_contents($path))));

return implode(' ', array(
substr($hash, 0, 8),
substr($hash, 8, 8),
substr($hash, 16, 8),
substr($hash, 24, 8),
'', 
 substr($hash, 32, 8),
substr($hash, 40, 8),
substr($hash, 48, 8),
substr($hash, 56, 8),
));
}
}
