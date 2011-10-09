<?php
/**
 * Default SEF URL scheme.
 *
 * @copyright (C) 2010 hcs
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package hcs_redirect_links
 */

$forum_url['hcs_redirect'] = 'misc.php?action=redirect&amp;hash=$1&amp;pid=$2';
$forum_url['hcs_redirect_sig'] = 'misc.php?action=redirect&amp;hash=$1&amp;sig=$2';
$forum_url['hcs_redirect_sigpost'] = 'misc.php?action=redirect&amp;hash=$1&amp;pid=$2&amp;sig=$3';
$forum_url['hcs_admin_redirect'] =	'admin/settings.php?section=redirect';
$forum_url['hcs_redirect_confirm'] = 'misc.php?action=redirect_confirm';
$forum_url['hcs_redirect_uid'] = 'misc.php?action=redirect&hash=$1&uid=$2';
?>