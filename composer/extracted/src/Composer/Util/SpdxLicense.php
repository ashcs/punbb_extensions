<?php











namespace Composer\Util;

use Composer\Spdx\SpdxLicenses;

trigger_error('The ' . __NAMESPACE__ . '\SpdxLicense class is deprecated, use Composer\Spdx\SpdxLicenses instead.', E_USER_DEPRECATED);




class SpdxLicense extends SpdxLicenses
{
}
