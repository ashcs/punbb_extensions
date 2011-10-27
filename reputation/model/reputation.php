<?php
/**
 * Reputation model class
 * 
 * @author hcs
 * @copyright (C) 2011 hcs reputation extension for PunBB
 * @copyright Copyright (C) 2011 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package reputation
 */
class Reputation_Model_Reputation
{
	
	public function get_user($user_id)
	{
		$query = array(
			'SELECT'	=> 'u.username, u.rep_plus AS count_rep_plus, u.rep_minus AS count_rep_minus',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.id='.$user_id
		);	
		
		$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);	
		
		return App::$forum_db->fetch_assoc($result);
	}
	
	public function get_by_id($id)
	{
		$query = array(
			'SELECT'	=> 'r.*, u.username',
			'FROM'		=> 'reputation AS r',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'			=> 'r.from_user_id = u.id'
				),
			),	
			'WHERE'		=> 'r.id='.$id
		);	
		$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);

		return App::$forum_db->fetch_assoc($result);
	}
	
	
	function count_by_user_id($user_id) 
	{
		$query = array(
			'SELECT'	=> 'count(id)',
			'FROM'		=> 'reputation',
			'WHERE'		=> 'user_id = '.$user_id
		);

		$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);

		list($count) = App::$forum_db->fetch_row($result);

		return $count;
	}	
	
	public function get_info($user_id, $group_id, $from, $to)
	{
		$query = array(
			'SELECT'	=> 'r.id, r.time, r.reason, r.comment, r.post_id, r.rep_plus, r.rep_minus, r.user_id, t.subject, u.username as from_user_name, u.id as from_user_id, fp.read_forum',
			'FROM'		=> 'reputation AS r',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'topics AS t',
					'ON'			=> 't.id=r.topic_id'
				),
				array(
					'LEFT JOIN'		=> 'users AS u',
					'ON'			=> 'r.from_user_id = u.id'
				),
				array(
					'LEFT JOIN'		=> 'forum_perms AS fp',
					'ON'			=> '(fp.forum_id=t.forum_id AND fp.group_id='.$group_id.')'
				)		
			),
			'WHERE'		=> 'r.user_id = '.$user_id,
			'ORDER BY'	=> 'r.time DESC',
			'LIMIT'		=> $from.','.$to		
		);	
		$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);	
	
		$records = array();
		while ($row = App::$forum_db->fetch_assoc($result))
		{
			$records[] = $row;
		}

		return $records;		
	}
	
	public function get_post_info($post_id, $user_id, $from_user_id, $time)
	{
		$query = array(
			'SELECT'	=> 'p.poster_id, p.id, p.topic_id, t.subject, u.rep_enable, u.username, r.time, r.post_id',
			'FROM'		=> 'posts AS p',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'topics AS t',
					'ON'			=> 'p.topic_id=t.id'
				),
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'p.poster_id = u.id'
				),
				array(
					'LEFT JOIN'		=> 'reputation as r',
					'ON'			=> '(r.from_user_id ='.$from_user_id .' AND r.user_id = u.id AND r.post_id ='.$post_id.') OR (r.from_user_id ='.$from_user_id .' AND r.user_id = u.id  AND r.time > '. $time.')'
				)
			),
			'WHERE'		=> 'p.id='.$post_id.' AND p.poster_id='. $user_id,
			'ORDER BY'	=>	'r.time DESC',
			'LIMIT'	=> '0, 1',
		);	
		$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);

		return App::$forum_db->fetch_assoc($result);
	}

	public function add_voice($target, $message, $from_user_id, $method)
	{
		$query = array(
			'INSERT'	=> 'user_id, from_user_id, time, post_id, reason, topic_id, rep_'. $method,
			'INTO'		=> 'reputation',
			'VALUES'		=> '\''.$target['poster_id'].'\', '.$from_user_id.', '.mktime().', '.$target['id'].', \''.App::$forum_db->escape($message).'\', '.$target['topic_id'].', 1',
		);	
		$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);		

		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'rep_'. $method.'='.'rep_'. $method.'+1',
			'WHERE'		=> 'id='.$target['poster_id']
		);
		App::$forum_db->query_build($query) or error(__FILE__, __LINE__);			
	}
	
	public function add_comment($rid, $message)
	{
		$query = array(
			'UPDATE'	=> 'reputation',
			'SET'		=> 'comment=\''.App::$forum_db->escape($message).'\'',
			'WHERE'		=> 'id = '.$rid
		);
	
		$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);	
	}	

	public function delete($user_id, $id_list)
	{
		$query = array(
			'DELETE'	=> 'reputation',
			'WHERE'		=> 'id IN('.$id_list.')'
		);
	
		$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'SELECT'	=> 'SUM(rep_plus) AS plus, SUM(rep_minus) AS minus',
			'FROM'		=> 'reputation',
			'WHERE'		=> 'user_id = '.$user_id,
			'GROUP BY'	=> 'user_id'
		);
	
		$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
			
		if (FALSE === ($rep = App::$forum_db->fetch_assoc($result)))
		{
			$rep['plus'] = 0;
			$rep['minus'] = 0;
		}
		
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'rep_plus='.$rep['plus'].',rep_minus='.$rep['minus'],
			'WHERE'		=> 'id = '.$user_id
		);
	
		$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);				
	}
}