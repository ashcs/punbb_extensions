<?php
/**
 * Make links redirectly
 *
 *	hcs_redirect_links
 * @copyright (C) 2011 hcs hcs@mail.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 *	Extension for PunBB (C) 2008-2011 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
defined('FORUM') OR die();

if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'.php'))
{
	require_once $ext_info['path'].'/lang/'.$forum_user['language'].'.php';
}
else
{
	require_once $ext_info['path'].'/lang/English.php';
}

function check_exclude_url($full_url)
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

function check_redirect(& $url, & $link, & $full_url, $tmp_link)
{
	global $forum_user, $cur_post, $forum_config, $base_url, $forum_url, $lang_redirect_links, $user;
	$no_replaced_url = $full_url;

	if (!check_exclude_url($full_url))
	{	
		if (isset($cur_post) && isset($cur_post['id']) && !isset($GLOBALS['hcs_sig_redirect']))
		{
			$full_url = forum_link($forum_url['hcs_redirect'], array(forum_hash($full_url, ''), $cur_post['id'])).'" target="_blank';
			replace_link(& $link, & $full_url, $no_replaced_url, $tmp_link );
		}
		else if (!isset($cur_post) && !isset($cur_post['id']) && isset($GLOBALS['hcs_sig_redirect']))
		{
			$full_url = forum_link($forum_url['hcs_redirect_sig'], array(forum_hash($full_url, ''), $user['id'])).'" target="_blank';
			replace_link(& $link, & $full_url, $no_replaced_url, $tmp_link );
		}
		else if (isset($cur_post) && isset($cur_post['id']) && isset($GLOBALS['hcs_sig_redirect']))
		{
			$full_url = forum_link($forum_url['hcs_redirect_sigpost'], array(forum_hash($full_url, ''),$cur_post['id'],$cur_post['poster_id'])).'" target="_blank';			
			replace_link(& $link, & $full_url, $no_replaced_url, $tmp_link );
		} 
	}
}

function replace_link($link, & $full_url, $no_replaced_url, $tmp_link )
{
	global $lang_redirect_links;
	if ($no_replaced_url == $link)
	{
		$link = str_replace('" target="_blank', '', $full_url);
	}
	if ($tmp_link == '')
	{
		 $link = $lang_redirect_links['External link'];
	}
}