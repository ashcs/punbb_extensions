<?php 
/**
 * Reputation installer
 * 
 * @author hcs
 * @copyright (C) 2012 hcs reputation extension for PunBB
 * @copyright Copyright (C) 2012 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package reputation
 */

defined('FORUM_ROOT') or die('Direct access not allowed');;

class Reputation_Model_Installer {

	public static $config = array(
		'o_reputation_enabled'		=> '1',
		'o_reputation_maxmessage'	=> '400',
		'o_reputation_show_full'	=>	'1'
	);
	
	public static $defaults = array(
		'g_rep_weight'	=> 1,
		'g_rep_timeout'	=> 300
	);

	private static $schema = array(
		'FIELDS' => array(
			'id' => array(
				'datatype' => 'SERIAL', 
				'allow_null' => false
			), 
			'user_id' => array(
				'datatype' => 'INT(10) UNSIGNED', 
				'allow_null' => false, 
				'default' => '0'
			), 
			'from_user_id' => array(
				'datatype' => 'INT(10) UNSIGNED', 
				'allow_null' => false, 
				'default' => '0'
			), 
			'time' => array(
				'datatype' => 'INT(10) UNSIGNED', 
				'allow_null' => false
			), 
			'post_id' => array(
				'datatype' => 'INT(10) UNSIGNED', 
				'allow_null' => false
			), 
			'topic_id' => array(
				'datatype' => 'INT(10) UNSIGNED', 
				'allow_null' => false
			), 
			'reason' => array(
				'datatype' => 'TEXT', 
				'allow_null' => false
			), 
			'comment' => array(
				'datatype' => 'TEXT', 
				'allow_null' => true
			), 
			'rep_plus' => array(
				'datatype' => 'TINYINT(1)', 
				'allow_null' => false, 
				'default' => '0'
			), 
			'rep_minus' => array(
				'datatype' => 'TINYINT(1)', 
				'allow_null' => false, 
				'default' => '0'
			)
		), 
		'PRIMARY KEY' => array(
			'id'
		), 
		'INDEXES' => array(
			'rep_post_id_idx' => array(
				'post_id'
			), 
			'rep_time_idx' => array(
				'time'
			), 
			'rep_multi_user_id_idx' => array(
				'from_user_id', 
				'topic_id'
			)
		)
	);	
	
	static function install()
	{
		App::$forum_db->create_table('reputation', self::$schema);
		self::update();
		
		App::$forum_db->add_field('users', 'rep_enable', 'TINYINT(1)', true, '1');
		App::$forum_db->add_field('users', 'rep_disable_adm', 'TINYINT(1)', true, '0');
		App::$forum_db->add_field('users', 'rep_minus', 'INT(10)', true, '0');
		App::$forum_db->add_field('users', 'rep_plus', 'INT(10)', true, '0');
		App::$forum_db->add_field('groups', 'g_rep_minus_min', 'INT(10)', true, '0');
		App::$forum_db->add_field('groups', 'g_rep_plus_min', 'INT(10)', true, '0');
		App::$forum_db->add_field('groups', 'g_rep_enable', 'TINYINT(1)', true, '1');
		App::$forum_db->add_field('reputation', 'comment', 'TEXT', true, '');
		App::$forum_db->add_index('reputation', 'rep_time_idx', array('time'));
		App::$forum_db->add_field('groups', 'g_rep_weight', 'INT(4)', true, self::$defaults['g_rep_weight']);
		App::$forum_db->add_field('groups', 'g_rep_timeout', 'INT(6)', true, self::$defaults['g_rep_timeout']);
		
		foreach (self::$config as $key => $value)
		{
			forum_config_add($key, $value);
		}		
	}	
	
	static function update()
	{
		// Check for version 2.*.* fields
		if (App::$forum_db->field_exists('reputation', 'topics_id'))
		{
			// convert to v 3.*.*
			/*
			* PGSQL: ALTER TABLE [table_name] RENAME COLUMN [column_name_1] TO [column_name_2]
			* mysql: ALTER TABLE [table_name] CHANGE [column_name_1] [column_name_2] [data_type]
			* sqlite: impossible
			*/
		
			switch ($GLOBALS['db_type'])
			{
				case 'mysql':
				case 'mysqli':
					$query = 'ALTER TABLE ' . $GLOBALS['db_prefix'] . 'reputation CHANGE topics_id topic_id INT(10)';
					App::$forum_db->query($query) or error(__FILE__, __LINE__);
					break;
					
				case 'pgsql':
					$query = 'ALTER TABLE ' . $GLOBALS['db_prefix'] . 'reputation RENAME COLUMN topics_id TO topic_id';
					App::$forum_db->query($query) or error(__FILE__, __LINE__);
					break;
					
				case 'sqlite':
						
					App::$forum_db->add_field('reputation', 'topic_id', 'INT(10) UNSIGNED', false);
					
					$query = array(
						'SELECT' => 'id, topics_id', 
						'FROM' => 'reputation'
					);
					
					$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
						
					while (TRUE == ($cur_rep = App::$forum_db->fetch_assoc($result)))
					{
						$query = array(
							'UPDATE' => 'reputation', 
							'SET' => 'topic_id=' . $cur_rep['topics_id'], 
							'WHERE' => 'id=' . $cur_rep['id']
						);
						App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
					}
					App::$forum_db->drop_field('reputation', 'topics_id');
						
					break;
			}
		}
		
		if (App::$forum_db->field_exists('users', 'reputation_enable'))
		{
			switch ($GLOBALS['db_type'])
			{
				case 'mysql':
				case 'mysqli':
					$query = 'ALTER TABLE ' . $GLOBALS['db_prefix'] . 'users CHANGE reputation_enable rep_enable TINYINT(1)';
					App::$forum_db->query($query) or error(__FILE__, __LINE__);
					break;
					
				case 'pgsql':
					$query = 'ALTER TABLE ' . $GLOBALS['db_prefix'] . 'users RENAME COLUMN reputation_enable TO rep_enable';
					App::$forum_db->query($query) or error(__FILE__, __LINE__);
					break;
					
				case 'sqlite':
					App::$forum_db->add_field('users', 'rep_enable', 'TINYINT(1)', true, '1');
					$query = array(
						'SELECT' => 'id, reputation_enable', 
						'FROM' => 'users'
					);
					
					$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
						
					while (TRUE == ($cur_rep = App::$forum_db->fetch_assoc($result)))
					{
						$query = array(
							'UPDATE' => 'users', 
							'SET' => 'rep_enable=' . $cur_rep['reputation_enable'], 
							'WHERE' => 'id=' . $cur_rep['id']
						);
						
						App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
					}
					App::$forum_db->drop_field('users', 'reputation_enable');
						
					break;
			}
		}
		
		if (App::$forum_db->field_exists('users', 'reputation_enable_adm'))
		{
		
			App::$forum_db->add_field('users', 'rep_disable_adm', 'TINYINT(1)', true, '0');
			
			$query = array(
				'SELECT' => 'id, reputation_enable_adm', 
				'FROM' => 'users'
			);
			
			$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
		
			while (TRUE == ($cur_rep = App::$forum_db->fetch_assoc($result)))
			{
				$query = array(
					'UPDATE' => 'users', 
					'SET' => 'rep_disable_adm=' . ($cur_rep['reputation_enable_adm'] == 1) ? 0 : 1, 
					'WHERE' => 'id=' . $cur_rep['id']
				);
				App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
			App::$forum_db->drop_field('users', 'reputation_enable_adm');
		}
		
	}
	
	public static function uninstall()
	{
		App::$forum_db->drop_table('reputation');
		App::$forum_db->drop_field('users', 'rep_minus');
		App::$forum_db->drop_field('users', 'rep_plus');
		App::$forum_db->drop_field('users', 'rep_enable');
		App::$forum_db->drop_field('users', 'rep_disable_adm');
		App::$forum_db->drop_field('groups', 'g_rep_minus_min');
		App::$forum_db->drop_field('groups', 'g_rep_plus_min');
		App::$forum_db->drop_field('groups', 'g_rep_enable');
		App::$forum_db->drop_field('groups', 'g_rep_weight');
		App::$forum_db->drop_field('groups', 'g_rep_timeout');
		
		forum_config_remove(array('o_reputation_enabled', 'o_reputation_timeout','o_reputation_maxmessage', 'o_reputation_show_full'));		
	}
}

