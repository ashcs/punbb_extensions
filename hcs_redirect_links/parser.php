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


if (file_exists($ext_info['path'].'/lang/'.$GLOBALS['forum_user']['language'].'.php'))
{
	require_once $ext_info['path'].'/lang/'.$GLOBALS['forum_user']['language'].'.php';
}
else
{
	require_once $ext_info['path'].'/lang/English.php';
}

function is_reserved_url($full_url)
{
	global $base_url, $forum_config;
	static $urls = array();
	if (empty($urls))
	{
		$urls = explode("\n",$forum_config['o_hcs_redirect_links']);
		array_push($urls, $base_url);
	}
	foreach ($urls as $cur_url)
	{
		if (strlen($cur_url)>0 && strlen($full_url))
		{
			if (FALSE !== strpos(forum_trim($full_url), forum_trim($cur_url))) 
				return TRUE;
		}
	}
	return FALSE;
}

function check_redirect(& $url, & $link, & $full_url)
{
	global $cur_post, $forum_url, $user;

	if (!is_reserved_url($full_url))
	{	
		if (isset($cur_post) && isset($cur_post['id']) && !isset($GLOBALS['hcs_sig_redirect']))
		{
			$full_url = forum_link($forum_url['hcs_redirect'], array(forum_hash($full_url, ''), $cur_post['id'])).'" target="_blank';
		}
		else if (!isset($cur_post) && !isset($cur_post['id']) && isset($GLOBALS['hcs_sig_redirect']))
		{
			$full_url = forum_link($forum_url['hcs_redirect_sig'], array(forum_hash($full_url, ''), $user['id'])).'" target="_blank';
		}
		else if (isset($cur_post) && isset($cur_post['id']) && isset($GLOBALS['hcs_sig_redirect']))
		{
			$full_url = forum_link($forum_url['hcs_redirect_sigpost'], array(forum_hash($full_url, ''),$cur_post['id'],$cur_post['poster_id'])).'" target="_blank';			
		} 
		$link = str_replace('http://', '', $link);
	}
}
