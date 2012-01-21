<?php
/**
 * Make links redirectly
 *
 *	hcs_redirect_links
 * @copyright (C) 2012 hcs hcs@mail.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 *	Extension for PunBB (C) 2008-2012 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
defined('FORUM') OR die();

function do_redirect($matches, $post_id, $user_id)
{
	global $forum_db, $lang_redirect_links;
	if (count($matches)>0)
	{
		foreach ($matches as $cur_url)
		{
			if (forum_hash(forum_htmlencode($cur_url), '') == $_POST['hash'])
			{
				// update counters, add if not exist
				$subquery = array();
				if ($post_id)
				{
					 $subquery[] = ' post_id='.$post_id;
				}
				if ($user_id)
				{
					$subquery [] = ' user_id='.$user_id;
				}
				$query = array(
					'SELECT'	=> '*',
					'FROM'		=> 'hcs_redirect_links',
					'WHERE'		=> 'link=\''.$forum_db->escape($cur_url).'\' AND '.implode(' AND ', $subquery)
				);
				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
				
				if (!$forum_db->num_rows($result))
				{
					$query = array(
						'INSERT'	=> 'link, counter, post_id, user_id',
						'INTO'		=> 'hcs_redirect_links',
						'VALUES'	=> '\''.$forum_db->escape($cur_url).'\', 1, \''.$post_id.'\', \''.$user_id.'\''
					);
				}
				else
				{
					$cur_redirect_link = $forum_db->fetch_assoc($result);
					$counter = $cur_redirect_link['counter'] + 1;
					$query = array(
						'UPDATE'	=> 'hcs_redirect_links',
						'SET'		=> 'counter = '. $counter,
						'WHERE'		=> 'link=\''.$forum_db->escape($cur_url).'\' AND '.implode(' AND ', $subquery)
					);
				}

				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
				
				redirect($cur_url, $lang_redirect_links['Go redirect']);
				
				exit();
			}
		}
	}
}	
	
	
if ($action == 'redirect')
{
	//if (!isset($_GET['pid']) && !isset($_GET['sig'])&& !isset($_GET['uid'])) 
	//	message($lang_common['Bad request'].'pid & sig');
	if (!isset($_GET['hash']))
		message($lang_common['Bad request'].'no hash');

		
	$pid = (isset($_GET['pid'])) ? intval ($_GET['pid']) : 0;
	$uid = (isset($_GET['sig'])) ? intval ($_GET['sig']) : 0;
	$profile_id = (isset($_GET['uid'])) ? intval ($_GET['uid']) : 0;

	if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'.php'))
	{
		require $ext_info['path'].'/lang/'.$forum_user['language'].'.php';
	}
	else
	{
		require $ext_info['path'].'/lang/English.php';
	}
	
	require $ext_info['path'].'/redirect_form.php';	
	
	exit();

}

if ($action == 'redirect_confirm')
{

	if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'.php'))
	{
		require $ext_info['path'].'/lang/'.$forum_user['language'].'.php';
	}
	else
	{
		require $ext_info['path'].'/lang/English.php';
	}

	if (isset($_POST['confirm_cancel']))
	{
		if (isset($_POST['prev_url']))
		{
			redirect($_POST['prev_url'],$lang_redirect_links['Redirect cancelled']);
		}
		else
		{
			redirect($base_url,$lang_redirect_links['Redirect cancelled']);
		}
	}		
		
	$pid = (isset($_POST['pid'])) ? intval ($_POST['pid']) : 0;
	$uid = (isset($_POST['uid'])) ? intval ($_POST['uid']) : 0;
	$profile_id = (isset($_POST['profile_id'])) ? intval ($_POST['profile_id']) : 0;
	
	if ($uid && !$pid)
	{
		$query = array(
			'SELECT'	=> 'u.signature, u.url, u.show_sig',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.id='.($uid) ? $uid : $profile_id
		);		
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		
		if (!$forum_db->num_rows($result))
			message($lang_common['Bad request']);

		$cur_info = $forum_db->fetch_assoc($result);
		$forum_message = $cur_info['signature'];
		
	}
	else if($profile_id)
	{
		$query = array(
			'SELECT'	=> 'u.url',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.id='.$profile_id
		);		
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		
		if (!$forum_db->num_rows($result))
			message($lang_common['Bad request']);

		$cur_info = $forum_db->fetch_assoc($result);
		$forum_message = $cur_info['url'];		
		
	}
	else
	{
		$query = array(
			'SELECT'	=> 'p.message, u.signature, u.show_sig',
			'FROM'		=> 'posts AS p',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id=p.poster_id'
				)
			),
			'WHERE'		=> 'p.id='.$pid
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		
		if (!$forum_db->num_rows($result))
			message($lang_common['Bad request']);

		$cur_info = $forum_db->fetch_assoc($result);

		$forum_message = $cur_info['message'].$cur_info['signature'];
	}
	
	if ($profile_id)
	{
		do_redirect(array($cur_info['url']), $pid, $profile_id);
	}
	
	$pattern = '#\[url\]([^\[]*?)\[/url\]#e';
	preg_match_all($pattern, $forum_message, $matches);

	do_redirect($matches[1], $pid, $uid);

	$pattern = '#\[url=([^\[]+?)\](.*?)\[/url\]#e';
	preg_match_all($pattern, $forum_message, $matches);

	do_redirect($matches[1], $pid,$uid);
	
	message($lang_common['Bad request']);
		
	
}