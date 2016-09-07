<?php
/**
 * Reputation uninstaller
 * 
 * @author hcs
 * @copyright (C) 2011 hcs reputation extension for PunBB
 * @copyright Copyright (C) 2011 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package reputation
 */

defined('REPUTATION_UNINSTALL') or die('Direct access not allowed');

$forum_db->drop_table('reputation');
$forum_db->drop_field('users', 'rep_minus');
$forum_db->drop_field('users', 'rep_plus');
$forum_db->drop_field('users', 'rep_enable');
$forum_db->drop_field('users', 'rep_disable_adm');
$forum_db->drop_field('groups', 'g_rep_minus_min');
$forum_db->drop_field('groups', 'g_rep_plus_min');
$forum_db->drop_field('groups', 'g_rep_enable');

$config_names  =  array('o_reputation_enabled', 'o_reputation_timeout','o_reputation_maxmessage', 'o_reputation_show_full');
$query = array(
	'DELETE'	=> 'config',
	'WHERE'		=> 'conf_name IN (\''.implode('\', \'', $config_names).'\')'
);
$forum_db->query_build($query) or error(__FILE__, __LINE__);