<?php
/**
 * Rewrite rules for URL scheme.
 *
 * @copyright (C) 2011 hcs reputation extension for PunBB (C)
 * @copyright Copyright (C) 2011 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package reputation
 */

$forum_rewrite_rules['/^reputation[\/_-]?view[\/_-]?([0-9a-z]+)(\.html?|\/)?$/i'] = 'misc.php?r=reputation/reputation/view/uid/$1';
$forum_rewrite_rules['/^reputation[\/_-]?reply[\/_-]?([0-9a-z]+)(\.html?|\/)?$/i'] = 'misc.php?r=reputation/reputation/comment/rid/$1';
$forum_rewrite_rules['/^reputation[\/_-]?view[\/_-]?([0-9a-z]+)[\/_-]?(p|page\/)([0-9]+)(\.html?|\/)?$/i'] = 'misc.php?r=reputation/reputation/view/uid/$1&p=$3';
$forum_rewrite_rules['/^reputation[\/_-]?delete[\/_-]?([0-9a-z]+)(\.html?|\/)?$/i'] = 'misc.php?r=reputation/reputation/delete/uid/$1';
$forum_rewrite_rules['/^reputation[\/_-]?(plus|minus)[\/_-]?([0-9a-z]+)[\/_-]?([0-9a-z]+)(\.html?|\/)?$/i'] = 'misc.php?r=reputation/reputation/$1/pid/$2/uid/$3';
