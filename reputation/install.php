<?php 
/**
 * Reputation installer
 * 
 * @author hcs
 * @copyright (C) 2011 hcs reputation extension for PunBB
 * @copyright Copyright (C) 2011 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package reputation
 */

defined('REPUTATION_INSTALL') or die('Direct access not allowed');

	if (!defined('EXT_CUR_VERSION')){
		if (!$forum_db->table_exists('reputation')) {
			$schema = array(
				'FIELDS' => array(
					'id'		=> array(
						'datatype'		=> 'SERIAL',
						'allow_null'	=> false
					),
					'user_id'	=> array(
						'datatype'		=> 'INT(10) UNSIGNED',
						'allow_null'	=> false,
						'default'		=> '0'
					),
					'from_user_id'	=> array(
						'datatype'		=> 'INT(10) UNSIGNED',
						'allow_null'	=> false,
						'default'		=> '0'
					),					
					'time'		=> array(
						'datatype'		=> 'INT(10) UNSIGNED',
						'allow_null'	=> false
					),					
					'post_id'	=> array(
						'datatype'		=> 'INT(10) UNSIGNED',
						'allow_null'	=> false
					),
					'topic_id'		=> array(
						'datatype'		=> 'INT(10) UNSIGNED',
						'allow_null'	=> false
					),
					'reason'		=> array(
						'datatype'		=> 'TEXT',
						'allow_null'	=> false
					),
					'comment'		=> array(
						'datatype'		=> 'TEXT',
						'allow_null'	=> true,
					),
					'rep_plus'			=> array(
						'datatype'		=> 'TINYINT(1)',
						'allow_null'	=> false,
						'default'		=> '0'
					),
					'rep_minus'			=> array(
						'datatype'		=> 'TINYINT(1)',
						'allow_null'	=> false,
						'default'		=> '0'
					)
				),
				'PRIMARY KEY'	=> array('id'),
				'INDEXES'		=> array(
					'rep_post_id_idx'	=> array('post_id'),
					'rep_time_idx'	=> array('time'),
					'rep_multi_user_id_idx'		=> array( 'from_user_id', 'topic_id')
				)				
			);
			$forum_db->create_table('reputation', $schema);
		}
		
		// Check for version 2.*.*  fields
		if ($forum_db->field_exists('reputation', 'topics_id')) {
			// convert to v 3.*.*
			/*
			PGSQL: ALTER TABLE [table_name] RENAME COLUMN [column_name_1] TO [column_name_2]
			mysql: ALTER TABLE [table_name] CHANGE [column_name_1] [column_name_2] [data_type]
			sqlite: impossible
			*/

			switch ($db_type){
				case 'mysql':
				case 'mysqli':
					$query = 'ALTER TABLE '.$db_prefix.'reputation CHANGE topics_id topic_id INT(10)';
					$forum_db->query($query) or error(__FILE__, __LINE__);
					break;
				case 'pgsql':
					$query = 'ALTER TABLE '.$db_prefix.'reputation RENAME COLUMN topics_id TO topic_id';
					$forum_db->query($query) or error(__FILE__, __LINE__);
					break;
				case 'sqlite':
				
					if (!$forum_db->field_exists('reputation', 'topic_id'))
						$forum_db->add_field('reputation', 'topic_id', 'INT(10) UNSIGNED', false);
					$query = array(
						'SELECT'	=> 'id, topics_id',
						'FROM'		=> 'reputation'
					);	
					$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			
					while ($cur_rep = $forum_db->fetch_assoc($result)){
						$query = array(
							'UPDATE'	=> 'reputation',
							'SET'		=> 'topic_id='.$cur_rep['topics_id'],
							'WHERE'		=> 'id='.$cur_rep['id'],
						);
						$forum_db->query_build($query) or error(__FILE__, __LINE__);
					}
					$forum_db->drop_field('reputation', 'topics_id');

					break;
			}
		}		
		
		if ($forum_db->field_exists('reputation', 'reputation_enable')) {
			switch ($db_type){
				case 'mysql':
				case 'mysqli':
					$query = 'ALTER TABLE '.$db_prefix.'users CHANGE reputation_enable rep_enable TINYINT(1)';
					$forum_db->query($query) or error(__FILE__, __LINE__);
					break;
				case 'pgsql':
					$query = 'ALTER TABLE '.$db_prefix.'users RENAME COLUMN reputation_enable TO rep_enable';
					$forum_db->query($query) or error(__FILE__, __LINE__);
					break;
				case 'sqlite':
				
					if (!$forum_db->field_exists('users', 'rep_enable'))
						$forum_db->add_field('users', 'rep_enable', 'TINYINT(1)', true, '1');
					$query = array(
						'SELECT'	=> 'id, reputation_enable',
						'FROM'		=> 'users'
					);	
					$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			
					while ($cur_rep = $forum_db->fetch_assoc($result)){
						$query = array(
							'UPDATE'	=> 'users',
							'SET'		=> 'rep_enable='.$cur_rep['reputation_enable'],
							'WHERE'		=> 'id='.$cur_rep['id'],
						);
						$forum_db->query_build($query) or error(__FILE__, __LINE__);
					}
					$forum_db->drop_field('users', 'reputation_enable');

					break;
			}		
		}

		if ($forum_db->field_exists('reputation', 'reputation_enable_adm')) {
		
			if (!$forum_db->field_exists('users', 'rep_disable_adm'))
				$forum_db->add_field('users', 'rep_disable_adm', 'TINYINT(1)', true, '0');
			$query = array(
				'SELECT'	=> 'id, reputation_enable_adm',
				'FROM'		=> 'users'
			);	
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	
			while ($cur_rep = $forum_db->fetch_assoc($result)){
				$query = array(
					'UPDATE'	=> 'users',
					'SET'		=> 'rep_disable_adm='.($cur_rep['reputation_enable_adm'] == 1) ? 0 : 1,
					'WHERE'		=> 'id='.$cur_rep['id'],
				);
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
			$forum_db->drop_field('users', 'reputation_enable_adm');
		}

		if (!$forum_db->field_exists('users', 'rep_enable'))
			$forum_db->add_field('users', 'rep_enable', 'TINYINT(1)', true, '1');
		if (!$forum_db->field_exists('users', 'rep_disable_adm'))
			$forum_db->add_field('users', 'rep_disable_adm', 'TINYINT(1)', true, '0');
		if (!$forum_db->field_exists('users', 'rep_minus'))
			$forum_db->add_field('users', 'rep_minus', 'INT(10)', true, '0');
		if (!$forum_db->field_exists('users', 'rep_plus'))
			$forum_db->add_field('users', 'rep_plus', 'INT(10)', true, '0');
		if (!$forum_db->field_exists('groups', 'g_rep_minus_min'))
			$forum_db->add_field('groups', 'g_rep_minus_min', 'INT(10)', true, '0');
		if (!$forum_db->field_exists('groups', 'g_rep_plus_min'))
			$forum_db->add_field('groups', 'g_rep_plus_min', 'INT(10)', true, '0');
		if (!$forum_db->field_exists('groups', 'g_rep_enable'))
			$forum_db->add_field('groups', 'g_rep_enable', 'TINYINT(1)', true, '1');
		$reputation_config = array(
			'o_reputation_enabled'			=> '1',
			'o_reputation_timeout'			=> '300',
			'o_reputation_maxmessage'			=> '400',
			'o_reputation_show_full'		=>	'1'
		);
		foreach ($reputation_config as $key => $value) {
			if(!array_key_exists($key, $forum_config)) {
				$query_reputation = array(
				'INSERT'	=> 'conf_name, conf_value',
					'INTO'		=> 'config',
					'VALUES'	=> '\''.$key.'\', \''.$forum_db->escape($value).'\''
				);
				$forum_db->query_build($query_reputation) or error(__FILE__, __LINE__);
			}
		}
		unset($query_reputation);		
		require_once FORUM_ROOT.'include/cache.php';
		generate_config_cache();
	}elseif (version_compare(EXT_CUR_VERSION, '3.0.1', '<=')){
		$query = array(
			'INSERT'	=> 'conf_name, conf_value',
			'INTO'		=> 'config',
			'VALUES'	=> '\'o_reputation_show_full\', \'1\''
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}elseif (version_compare(EXT_CUR_VERSION, '3.1.0', '<=')){
		$forum_db->add_field('reputation', 'comment', 'TEXT', true, '');
		$forum_db->add_index('reputation', 'rep_time_idx', array('time'));
	}
