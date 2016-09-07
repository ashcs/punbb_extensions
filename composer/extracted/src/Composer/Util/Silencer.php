<?php










namespace Composer\Util;






class Silencer
{



private static $stack = array();







public static function suppress($mask = null)
{
if (!isset($mask)) {
$mask = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED | E_STRICT;
}
$old = error_reporting();
array_push(self::$stack, $old);
error_reporting($old & ~$mask);

return $old;
}




public static function restore()
{
if (!empty(self::$stack)) {
error_reporting(array_pop(self::$stack));
}
}










public static function call($callable )
{
try {
self::suppress();
$result = call_user_func_array($callable, array_slice(func_get_args(), 1));
self::restore();

return $result;
} catch (\Exception $e) {

 self::restore();
throw $e;
}
}
}
