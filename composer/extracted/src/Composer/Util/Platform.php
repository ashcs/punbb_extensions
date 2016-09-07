<?php










namespace Composer\Util;






class Platform
{



public static function isWindows()
{
return defined('PHP_WINDOWS_VERSION_BUILD');
}
}
