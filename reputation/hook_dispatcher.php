<?php
/**
 * Reputation hook dispatcher class
 * 
 * 
 * @author hcs
 * @copyright (C) 2011 hcs reputation extension for PunBB
 * @copyright Copyright (C) 2011 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package reputation
 */
class Reputation_Hook_Dispatcher extends Base
{
	/**
 	 * Front-end hook dispatcher
	 * Inject hooks for showing reputation in topic messages
	 */
	public function front_end_init()
	{
		App::load_language('reputation.reputation');
		App::inject_hook('vt_qr_get_posts',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::vt_qr_get_posts($query, $forum_user[\'id\'], App::$now);'
		));
		App::inject_hook('vt_row_pre_post_actions_merge',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::vt_row_pre_post_actions_merge($cur_post,$forum_user);'
		));
	}
	
	public function vt_qr_get_posts(& $query, $user_id, $time)
	{
		$query['SELECT'] .= ', u.rep_plus, u.rep_minus, u.rep_enable, u.rep_disable_adm, r.id as rep_id';
		$query['JOINS'][] = array(
			'LEFT JOIN'	=> 'reputation AS r',
			'ON'			=> '(r.post_id = p.id AND r.from_user_id = '.$user_id.') OR (r.user_id = u.id AND r.from_user_id = '.$user_id.' AND r.time > '. $time.')'
		);	
	}
	
	/**
	 * Hook vt_row_pre_post_actions_merge handler
	 * Prepare user reputation for showing in topic messages
	 * 
	 * @param $cur_post
	 * @param $forum_user
	 */
	public function vt_row_pre_post_actions_merge($cur_post, $forum_user)
	{
		if ($cur_post['poster_id']!=1 && $forum_user['g_rep_enable'] == 1 && App::$forum_config['o_reputation_enabled'] == 1 && $cur_post['rep_enable'] == 1 && $forum_user['rep_disable_adm'] == 0 && $forum_user['rep_enable'] == 1)
		{
			App::$forum_page['author_info']['reputation'] = '<li><span><a href="'.forum_link(App::$forum_url['reputation_view'], $cur_post['poster_id']).'">'.App::$lang['Reputation'].'</a> : ';
			
			if(!$forum_user['is_guest'] AND $forum_user['id'] != $cur_post['poster_id'] AND $cur_post['rep_id'] == NULL)
			{
				if (App::$forum_user['g_rep_plus_min'] < App::$forum_user['num_posts'])
				{
					App::$forum_page['author_info']['reputation'] .= '<a href="'.forum_link(App::$forum_url['reputation_plus'], array($cur_post['id'],$cur_post['poster_id'])).'"><img src="'.forum_link('extensions/reputation').'/img/warn_add.gif" alt="+"></a>&nbsp;&nbsp;';
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
 					App::$forum_page['author_info']['reputation'] .= '&nbsp;&nbsp;<a href="'. forum_link(App::$forum_url['reputation_minus'], array($cur_post['id'],$cur_post['poster_id'])) .'"><img src="'.forum_link('extensions/reputation').'/img/warn_minus.gif" alt="-"></a></span></li>';
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

		View::$path = FORUM_ROOT.'extensions/reputation/';
	}		
	
	/**
	 * Hook agr_add_edit_group_flood_fieldset_end handler
	 * Show admin group setting form for reputation
	 * 
	 * @param $group
	 */
	public function agr_add_edit_group_flood_fieldset_end($group)
	{
		View::$instance = View::factory('admin_group_setting', array('group' => $group));	
		echo  View::$instance->render();
	}
	
	/**
	 * Hook aop_features_message_fieldset_end handler
	 * Show global reputation setting form
	 */
	public function aop_features_message_fieldset_end()
	{
		$forum_page['group_count'] = $forum_page['item_count'] = 0;
		View::$instance = View::factory('admin_options_features', array('forum_page' => $forum_page));	
		echo  View::$instance->render();
	}	

	/**
	 * 
	 * @param unknown_type $query
	 * @param unknown_type $is_admin_group
	 */
	public function agr_edit_end_qr_update_group(& $query, $is_admin_group)
	{
		$rep_enable = (isset($_POST['rep_enable']) && $_POST['rep_enable'] == '1') || $is_admin_group ? '1' : '0';
		$rep_minus_min = isset($_POST['rep_minus_min']) ? intval($_POST['rep_minus_min']) : '0';
		$rep_plus_min = isset($_POST['rep_plus_min']) ? intval($_POST['rep_plus_min']) : '0';
		$query['SET'] .= ', g_rep_enable= '.$rep_enable.', g_rep_minus_min='.$rep_minus_min.', g_rep_plus_min='.$rep_plus_min;
	}
	
	public function aop_features_validation(& $form)
	{
		if (!isset($form['reputation_enabled']) || $form['reputation_enabled'] != '1') $form['reputation_enabled'] = '0';
		if (!isset($form['reputation_show_full']) || $form['reputation_show_full'] != '1') $form['reputation_show_full'] = '0';
		$form['reputation_maxmessage'] = intval($form['reputation_maxmessage']);
		$form['reputation_timeout'] = intval($form['reputation_timeout']);		
	}
	
	public function profile_init()
	{
		App::load_language('reputation.reputation');
		
		App::inject_hook('pf_change_details_settings_local_fieldset_end',array(
			'name'	=>	'reputation',
			'code'	=>	'Reputation_Hook_Dispatcher::pf_change_details_settings_local_fieldset_end($user, $lang_profile);'
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
		
		View::$path = FORUM_ROOT.'extensions/reputation/';
	}		

	public function pf_change_details_settings_local_fieldset_end($user, $lang_profile)
	{
		$forum_page['group_count'] = $forum_page['item_count'] = 0;
		View::$instance = View::factory('profile_settings', array('user' => $user, 'lang_profile' => $lang_profile));	
		echo  View::$instance->render();
	}	
	
	public function pf_change_details_settings_validation($user, & $form)
	{
		if (App::$forum_user['is_admmod'] && $user['id'] != App::$forum_user['id']) {
			$form['rep_disable_adm'] = (isset($_POST['form']['rep_disable_adm'])) ? 1 :0;
		}
		else { 
		 	$form['rep_enable'] = (isset($_POST['form']['rep_enable'])) ? 1 :0; 
		}
	}	

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
			App::$forum_page['user_info']['reputation'] = '<li><span><a href="'.forum_link(App::$forum_url['reputation_view'], $user['id']).'">'.App::$lang['Reputation'].': <strong>[ + '.$user['rep_plus'].' | '. $user['rep_minus'].' - ]</strong></span></li></a> ';
		}
	}	
	
	public function pf_delete_user_form_submitted($id)
	{
		$query = array(
			'DELETE'	=> 'reputation',
			'WHERE'		=> 'user_id='.$id
		);
		App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}	
	
} 
