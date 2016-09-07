<?php
/**
 * Rewrite rules for URL scheme.
 *
 * @copyright (C) 2016 hcs Uploader extension for PunBB (C)
 * @copyright Copyright (C) 2016 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Uploader
 */

$forum_rewrite_rules['/^uploader[\/_-]download[\/_-]?([0-9]+)(\.html?|\/)?$/i'] = 'misc.php?r=hcs_uploader/downloader/download/id/$1';
$forum_rewrite_rules['/^uploader[\/_-]upload(\.html?|\/)?$/i'] = 'misc.php?r=hcs_uploader/uploader/upload';
$forum_rewrite_rules['/^uploader[\/_-]remove(\.html?|\/)?$/i'] = 'misc.php?r=hcs_uploader/uploader/remove';
$forum_rewrite_rules['/^uploader[\/_-]delete[\/_-]?([0-9]+)(\.html?|\/)?$/i'] = 'misc.php?r=hcs_uploader/uploader/delete/id/$1';