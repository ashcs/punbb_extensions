<?php
/**
 * Default SEF URL scheme.
 *
 * @copyright (C) 2016 hcs Uploader extension for PunBB (C)
 * @copyright Copyright (C) 2016 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Uploader
 */

$forum_url['uploader_admin'] = 'admin/settings.php?section=route&r=hcs_uploader/admin/index';
$forum_url['uploader_admin_alone'] = 'admin/settings.php?section=route&r=hcs_uploader/admin_files/files_list';
$forum_url['uploader_admin_alone_delete'] = 'admin/settings.php?section=route&r=hcs_uploader/admin_files/delete';
$forum_url['uploader_admin_setup'] = 'admin/settings.php?section=route&r=hcs_uploader/admin_uploader/index';
$forum_url['uploader_admin_setup_update'] = 'admin/settings.php?section=route&r=hcs_uploader/admin/update';

$forum_url['uploader_file_link'] = 'misc.php?r=hcs_uploader/downloader/download/id/$1';
$forum_url['uploader_file_upload'] = 'misc.php?r=hcs_uploader/uploader/upload';
$forum_url['uploader_file_remove'] = 'misc.php?r=hcs_uploader/uploader/remove';
$forum_url['uploader_file_delete'] = 'misc.php?r=hcs_uploader/uploader/delete/id/$1';
