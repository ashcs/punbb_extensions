<?php
/**
 * Reputation hook dispatcher class
 * 
 * 
 * @author hcs
 * @copyright (C) 2011 hcs reputation extension for PunBB Copyright (C) 2011 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package reputation
 */
class Reputation_Hook_Dispatcher extends Base {
	/**
 	 * Front-end hook dispatcher
	 * Inject hooks for showing reputation in topic messages
	 */
	public function front_end_init()
	{
		App::$forum_loader->add_css('.rep_plus_minus { font-style:italic; font-size: 90%; border-radius: 8px 8px; background-color:#F3F3F3; padding: 6px 12px !important;} .rep_plus_head { font-style:normal; color:#008000; } .rep_minus_head { font-style:normal; color:#FF0000; }', array('type' => 'inline'));
		App::$forum_loader->add_css($GLOBALS['ext_info']['url'].'/css/style.css', array('type' => 'url'));
		$GLOBALS['ext_jQuery_UI']->add_jQuery_UI_style(' .ui-widget {font-size: 0.8em;} .validateTips { border: 1px solid transparent; padding: 0.3em; }', 'ui_dailog_02'); // добавляем переопределение стиля в footer
		
		App::load_language('reputation.reputation');
		App::inject_hook('vt_qr_get_posts',array(
			'name'	=>	'reputation',
			'url'	=>	$GLOBALS['ext_info']['url'],
			'code'	=>	'Reputation_Hook_Dispatcher::vt_qr_get_posts($query, $forum_user[\'id\'], App::$now, $posts_id);'
		));
		App::inject_hook('vt_row_pre_post_actions_merge',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::vt_row_pre_post_actions_merge($cur_post,$forum_user);'
		));
		App::inject_hook('vt_row_pre_display',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::vt_row_pre_display($forum_page, $cur_post);'
		));		
	}

	/**
	 * Hook vt_row_pre_display handler
	 * Create block reputation info
	 * 
	 * @param array $forum_page
	 * @param array $cur_post
	 */	
	public function vt_row_pre_display(& $forum_page, $cur_post)
	{
		$bufer = array ('minus' => array(), 'plus' => array());
		if (isset($forum_page['reputation_info'][$cur_post['id']]))
		{
			foreach ($forum_page['reputation_info'][$cur_post['id']] as $cur_rep_info )
			{
				if ($cur_rep_info['rep_minus'])
				{
					$bufer['minus'][]= '<a href="'.forum_link(App::$forum_url['user'], $cur_rep_info['from_user_id']).'" rel="'.forum_link(App::$forum_url['reputation_by_id'], $cur_rep_info['rep_id']).'">'.forum_htmlencode($cur_rep_info['username']).'</a>';
				}
				if ($cur_rep_info['rep_plus'])
				{
					$bufer['plus'][]= '<a href="'.forum_link(App::$forum_url['user'], $cur_rep_info['from_user_id']).'" rel="'.forum_link(App::$forum_url['reputation_by_id'], $cur_rep_info['rep_id']).'">'.forum_htmlencode($cur_rep_info['username']).'</a>';
				}
			}

			$reputation = array();
			
			if (!empty($bufer['plus']))
			{
				$reputation[] = '<div class="rep_plus_minus"><span class="rep_plus_head">'.App::$lang['Positive assessed'].'</span><span>'.implode(', ', $bufer['plus']).'</span></div>';
			}
			
			if (!empty($bufer['minus']))
			{
				$reputation[] = '<div class="rep_plus_minus"><span class="rep_minus_head">'.App::$lang['Negative assessed'].'</span><span>'.implode(', ', $bufer['minus']).'</span></div>';
			}
					
			if (!empty($reputation))
			{
				if (!isset($forum_page['message']['signature']))
				{
					$forum_page['message']['reputation'] = '<div class="sig-content"><span class="sig-line"><!-- --></span>'.implode('<br /> ', $reputation).'</div>';
				}
				else
				{
					$forum_page['message']['reputation'] = '<div class="sig-content">'.implode('<br /> ', $reputation).'</div>';
				}
			}	
		}
	}
	
	/**
	 * Hook vt_qr_get_posts handler
	 * Change standart query for collect reputation info
	 * Prepare UI dialog
	 * 
	 * @param array $query
	 * @param int $user_id
	 * @param int $time
	 * @param int $posts_id
	 */
	public function vt_qr_get_posts(& $query, $user_id, $time, $posts_id)
	{
		$GLOBALS['ext_jQuery_UI']->add_jQuery_UI("Dialog");
		$GLOBALS['ext_jQuery_UI']->add_jQuery_UI("Fade");
		$GLOBALS['ext_jQuery_UI']->add_jQuery_UI("Resizable");
		$GLOBALS['ext_jQuery_UI']->add_jQuery_UI("Draggable");
		$GLOBALS['ext_jQuery_UI']->add_jQuery_UI("Button");
		
		$rep_js_env = '
    		PUNBB.env.rep_vars = {
				"Reason" : "'.App::$lang['Form reason'].'"
		    };';

		App::$forum_loader->add_js($rep_js_env, array('type' => 'inline'));
		App::$forum_loader->add_js($GLOBALS['ext_info']['url'].'/js/reputation.js', array('type' => 'url'));
		
		
			
		$GLOBALS['forum_page']['reputation_info'] = array();
		$query_rep = array(
			'SELECT'	=> 'r.id AS rep_id, r.post_id, u.username, r.from_user_id, r.rep_plus, r.rep_minus, r.time AS rep_time',
			'FROM'		=> 'reputation AS r',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id = r.from_user_id'
				),
			),
			'WHERE'		=> 'r.post_id IN ('.implode(',', $posts_id).')'
		);
		
		$rep_result = App::$forum_db->query_build($query_rep) or error(__FILE__, __LINE__);
		
		while($cur_rep = App::$forum_db->fetch_assoc($rep_result))
		{
			$GLOBALS['forum_page']['reputation_info'][$cur_rep['post_id']][] = $cur_rep;
		}
/**
 * 
 * TODO:
 * make query separately
 * temporary fix
 */		
		//$query['SELECT'] .= ', u.rep_plus, u.rep_minus, u.rep_enable, u.rep_disable_adm, r.id as rep_id';
		$query['SELECT'] .= ', u.rep_plus, u.rep_minus, u.rep_enable, u.rep_disable_adm';
		/*
		$query['JOINS'][] = array(
			'LEFT JOIN'	=> 'reputation AS r',
			'ON'			=> '(r.post_id = p.id AND r.from_user_id = '.$user_id.') OR (r.user_id = u.id AND r.from_user_id = '.$user_id.' AND r.time > '. $time.')'
		);	
		*/
		$GLOBALS['forum_page']['time'] = App::$now - App::$forum_config['o_reputation_timeout']*60;
	}
	
	/**
	 * Hook vt_row_pre_post_actions_merge handler
	 * Prepare user reputation for showing in topic messages
	 * 
	 * @param int $cur_post
	 * @param array $forum_user
	 */
	public function vt_row_pre_post_actions_merge($cur_post, $forum_user)
	{
		if ($cur_post['poster_id']!=1 && $forum_user['g_rep_enable'] == 1 && App::$forum_config['o_reputation_enabled'] == 1 && $cur_post['rep_enable'] == 1 && $forum_user['rep_disable_adm'] == 0 && $forum_user['rep_enable'] == 1)
		{
			App::$forum_page['author_info']['reputation'] = '<li><span><a href="'.forum_link(App::$forum_url['reputation_view'], $cur_post['poster_id']).'">'.App::$lang['Reputation'].'</a> : ';
			
			if(!$forum_user['is_guest'] AND $forum_user['id'] != $cur_post['poster_id'])// AND $cur_post['rep_id'] == NULL)// AND $GLOBALS['forum_page']['reputation_info'][$cur_post['id']]['rep_time'] < $GLOBALS['forum_page']['time'])
			{
				if (App::$forum_user['g_rep_plus_min'] < App::$forum_user['num_posts'])
				{
					App::$forum_page['author_info']['reputation'] .= '<a class="rep_info_link" href="'.forum_link(App::$forum_url['reputation_plus'], array($cur_post['id'],$cur_post['poster_id'])).'"><img src="'.forum_link('extensions/reputation').'/img/warn_add.gif" alt="+"></a>&nbsp;&nbsp;';
				}
				
				if (App::$forum_config['o_reputation_show_full']== '1' )
				{
 					App::$forum_page['author_info']['reputation'] .= '[ <span style="color:green">'.$cur_post['rep_plus'] . '</span> | <span style="color:red">'. $cur_post['rep_minus'] . '</span> ]';
 				} 
 				else 
 				{       
					App::$forum_page['author_info']['reputation'] .= $cur_post['rep_plus'] - $cur_post['rep_minus'];
 				}
 				
 				if (App::$forum_user['g_rep_minus_min'] < App::$forum_user['num_posts'])
 				{
 					App::$forum_page['author_info']['reputation'] .= '&nbsp;&nbsp;<a class="rep_info_link" href="'. forum_link(App::$forum_url['reputation_minus'], array($cur_post['id'],$cur_post['poster_id'])) .'"><img src="'.forum_link('extensions/reputation').'/img/warn_minus.gif" alt="-"></a></span></li>';
 				}
 				 
    		}  
    		else
    		{
				if (App::$forum_config['o_reputation_show_full']== '1' ) 
				{
 					App::$forum_page['author_info']['reputation'] .= '[ <span style="color:green">'.$cur_post['rep_plus'] . '</span> | <span style="color:red">'. $cur_post['rep_minus'] . '</span> ]';
 				}
 				else
 				{       
        			App::$forum_page['author_info']['reputation'] .= $cur_post['rep_plus'] - $cur_post['rep_minus'];
 				}
 				
				App::$forum_page['author_info']['reputation'] .= '</span></li>';
    		}
		}			
	}

	/*
	 * Back-end hook  dispatcher
	 * Inject hooks for manage global admin options of the reputation
	 */
	
	public function back_end_init()
	{
		App::load_language('reputation.reputation');
		
		App::inject_hook('agr_edit_end_qr_update_group',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::agr_edit_end_qr_update_group($query, $is_admin_group);'
		));
		
		App::inject_hook('agr_add_edit_group_flood_fieldset_end',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::agr_add_edit_group_flood_fieldset_end($group);'
		));

		App::inject_hook('aop_features_message_fieldset_end',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::aop_features_message_fieldset_end();'
		));
		
		App::inject_hook('aop_features_validation',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::aop_features_validation($form);'
		));		
	}		
	
	/**
	 * Hook agr_add_edit_group_flood_fieldset_end handler
	 * Show admin group setting form for reputation
	 * 
	 * @param int $group
	 */
	public function agr_add_edit_group_flood_fieldset_end($group)
	{
		View::$instance = View::factory(FORUM_ROOT.'extensions/reputation/view/admin_group_setting', array('group' => $group));	
		echo  View::$instance->render();
	}
	
	/**
	 * Hook aop_features_message_fieldset_end handler
	 * Show global reputation setting form
	 */
	public function aop_features_message_fieldset_end()
	{
		$forum_page['group_count'] = $forum_page['item_count'] = 0;
		View::$instance = View::factory(FORUM_ROOT.'extensions/reputation/view/admin_options_features', array('forum_page' => $forum_page));	
		echo  View::$instance->render();
	}	

	/**
	 * Hook agr_edit_end_qr_update_group handler
	 * @param array $query 
	 * @param bool $is_admin_group
	 */
	public function agr_edit_end_qr_update_group(& $query, $is_admin_group)
	{
		$rep_enable = (isset($_POST['rep_enable']) && $_POST['rep_enable'] == '1') || $is_admin_group ? '1' : '0';
		$rep_minus_min = isset($_POST['rep_minus_min']) ? intval($_POST['rep_minus_min']) : '0';
		$rep_plus_min = isset($_POST['rep_plus_min']) ? intval($_POST['rep_plus_min']) : '0';
		$query['SET'] .= ', g_rep_enable= '.$rep_enable.', g_rep_minus_min='.$rep_minus_min.', g_rep_plus_min='.$rep_plus_min;
	}
	
	/**
	 * Hook aop_features_validation handler
	 * @param $form
	 */
	public function aop_features_validation(& $form)
	{
		if (!isset($form['reputation_enabled']) || $form['reputation_enabled'] != '1')
		{
			$form['reputation_enabled'] = '0';
		}
		if (!isset($form['reputation_show_full']) || $form['reputation_show_full'] != '1')
		{
			$form['reputation_show_full'] = '0';
		}
		$form['reputation_maxmessage'] = intval($form['reputation_maxmessage']);
		$form['reputation_timeout'] = intval($form['reputation_timeout']);		
	}
	
	/**
	 * Profile dispatcher init
	 */
	public function profile_init()
	{
		App::load_language('reputation.reputation');
		
		App::inject_hook('pf_change_details_settings_local_fieldset_end',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::pf_change_details_settings_local_fieldset_end($user);'
		));

		App::inject_hook('pf_change_details_settings_validation',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::pf_change_details_settings_validation($user, $form);'
		));		

		App::inject_hook('pf_change_details_about_pre_header_load',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::pf_change_details_about_pre_header_load($user);'
		));
		
		App::inject_hook('pf_delete_user_form_submitted',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::pf_delete_user_form_submitted($id);'
		));				
	}		

	/**
	 * Hook pf_change_details_settings_local_fieldset_end handler
	 * @param array $user 
	 * @param array $lang_profile 
	 */
	public function pf_change_details_settings_local_fieldset_end($user)
	{
		View::$instance = View::factory(FORUM_ROOT.'extensions/reputation/view/profile_settings', array('user' => $user));
		echo  View::$instance->render();
	}	
	
	/**
	 * Hook pf_change_details_settings_validation handler
	 * @param int $user user id
	 * @param array $form form data array
	 */
	public function pf_change_details_settings_validation($user, & $form)
	{
		if (App::$forum_user['is_admmod'] && $user['id'] != App::$forum_user['id'])
		{
			$form['rep_disable_adm'] = (isset($_POST['form']['rep_disable_adm'])) ? 1 :0;
		}
		else 
		{ 
		 	$form['rep_enable'] = (isset($_POST['form']['rep_enable'])) ? 1 :0; 
		}
	}	

	/**
	 * Hook pf_change_details_about_pre_header_load handler
	 * @param array $user user data
	 */
	public function pf_change_details_about_pre_header_load($user)
	{
		if ($user['rep_disable_adm'] == 1)
		{
			App::$forum_page['user_info']['reputation'] = '<li><span>'.App::$lang['Individual Disabled'].'</span></li></a> ';
		}
		else if ($user['rep_enable'] == 0)
		{
			App::$forum_page['user_info']['reputation'] = '<li><span>'.App::$lang['User Disable'].'</span></li></a> ';
		}
		else
		{			
			App::$forum_page['user_info']['reputation'] = '<li><span><a href="'.forum_link(App::$forum_url['reputation_view'], $user['id']).'">'.App::$lang['Reputation'].':</a> <strong>[ + '.$user['rep_plus'].' | '. $user['rep_minus'].' - ]</strong></span></li>';
		}
	}	

	/**
	 * Hook pf_delete_user_form_submitted handler
	 * @param int $id user id for delete reputation
	 */
	public function pf_delete_user_form_submitted($id)
	{
		$query = array(
			'DELETE'	=> 'reputation',
			'WHERE'		=> 'user_id='.$id
		);
		App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}	
	
} 
