<?php
/**
 * Reputation controller class
 * 
 * @author hcs
 * @copyright (C) 2012 hcs reputation extension for PunBB
 * @copyright Copyright (C) 2012 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package reputation
 */
class Reputation_Controller_Reputation extends Controller
{
	protected $reputation;
	
	public function __construct($ext_path)
	{
		parent::__construct($ext_path);
		App::load_language('reputation.reputation');
		$this->check_access();
		$this->set_filter(array('uid' => 'int',	'pid' => 'int',	'rid' => 'int'));		
		$this->reputation = new Reputation_Model_Reputation;
		$this->page = 'reputation';
	}

	public function check_access()
	{
		if (App::$forum_user['g_rep_enable'] == 0)
			message(App::$lang['Group Disabled']);
		
		if (App::$forum_user['rep_disable_adm'] == 1)
			message(App::$lang['Individual Disabled']);
		
		if (App::$forum_config['o_reputation_enabled'] == 0)
			message(App::$lang['Disabled']);
		
		if (App::$forum_user['rep_enable'] == 0)
			message(App::$lang['Your Disabled']);		
	}

	public function view()
	{
		if (isset($this->id))
		{
			if (FALSE === ($user_rep = $this->reputation->get_by_id($this->id)))
				message(App::$lang_common['Bad request']);

			// Fix notice for fancy_video			
			global $smilies, $forum_user, $ext_info;
			if (!defined('FORUM_PARSER_LOADED'))
			{
				require FORUM_ROOT.'include/parser.php';
			}	
			$user_rep['reason'] = parse_message($user_rep['reason'], 0);
				
				
			App::send_json(array('message' => $user_rep['reason']));
		}
		
		if (FALSE === ($user_rep = $this->reputation->get_user($this->uid)))
			message(App::$lang_common['Bad request']); 

		App::$forum_page['form_action'] = forum_link(App::$forum_url['reputation_delete'], $this->uid);
		View::$instance = View::factory($this->view.'view', array ('heading' => sprintf(App::$lang['User reputation'], forum_htmlencode($user_rep['username'])) . '&nbsp;&nbsp;<strong>[+'. $user_rep['count_rep_plus'] . ' / -' . $user_rep['count_rep_minus'] .'] &nbsp;</strong>'));
		$count = $this->reputation->count_by_user_id($this->uid);
		
		if ($count > 0)
		{
			// Fix notice for fancy_video
			global $smilies, $forum_user, $ext_info;
			if (!defined('FORUM_PARSER_LOADED'))
			{
				require FORUM_ROOT.'include/parser.php';
			}			
			App::paginate($count, App::$forum_user['disp_topics'], App::$forum_url['reputation_view'],array($this->uid));
			
			if (App::$forum_user['g_id'] == FORUM_ADMIN)
			{
				/*
				 * Fix table layout described on: http://punbb.ru/post31786.html#p31786
				 */
				App::$forum_loader->add_css('#brd-reputation table{table-layout:inherit;}', array('type' => 'inline'));
				$template = 'view_admin';
			}
			else
			{
				$template = 'view_user';
			}
			View::$instance->content = View::factory($this->view.$template, array ('records' => $this->reputation->get_info($this->uid, App::$forum_user['g_id'], App::$forum_page['start_from'], App::$forum_page['finish_at']))); 
		}
		else {
			
			View::$instance->content = View::factory($this->view.'view_empty', array ('lang' => App::$lang));	
		}
	
		App::$forum_page['crumbs'][] = array(sprintf(App::$lang['User reputation'], forum_htmlencode($user_rep['username'])), forum_link(App::$forum_url['reputation_view'], $this->uid));
	}
	
	public function delete()
	{
		if (!isset($_POST['delete_rep_id'])) {
/*
 * TODO
 * Add info for signal of empty ids
 */			
			$this->view();
			return;
		}
		
		$idlist = implode(',',array_map(array($this, '_check_int_val'), $_POST['delete_rep_id']));
		$this->reputation->delete($this->uid, $idlist);
		
		App::$forum_flash->add_info(App::$lang['Deleted redirect']);
		redirect(forum_link(App::$forum_url['reputation_view'], array($this->uid)), App::$lang['Deleted redirect']);
	}
	
	public function plus()
	{
		$this->do_action('plus');
	}
	
	public function minus()
	{
		$this->do_action('minus');
	}
	
	private function do_action($action)
	{
		$target = $this->pre_process($action);
		$errors = array();
		
		if (isset($_POST['form_sent']))
		{
			if ($this->add_voice($errors, $target, $action))
			{
	    		App::$forum_flash->add_info(App::$lang['Redirect Message']);
    			redirect(forum_link(App::$forum_url['post'], $this->pid), App::$lang['Redirect Message']);			
			}
		}	
			
		App::$forum_page['form_action'] = forum_link(App::$forum_url['reputation_'.$action], array($this->pid, $this->uid));
		
		if (App::$is_ajax) 
		{
			if (empty($errors))
			{
				App::send_json(array(		
					'csrf_token'=> generate_form_token(App::$forum_page['form_action']),
					'title'		=> App::$lang['Reputation'],
					'description'=> sprintf(App::$lang[ucfirst($action)], forum_htmlencode($target['username'])),
					'user'		=>  $target['username'],
					'cancel'	=>  forum_htmlencode(App::$lang_common['Cancel']),
					'submit'	=>  forum_htmlencode(App::$lang_common['Submit'])
				));
			}
			else 
			{
				App::send_json(array(
					'error'	=> implode('<br />',$errors),
				));				
			}
		}		
		
		View::$instance = View::factory($this->view.'form', array('heading' => sprintf(App::$lang[ucfirst($action)],forum_htmlencode($target['username']))));
		View::$instance->errors = View::factory($this->view.'errors', array('errors'=>$errors, 'head' => App::$lang['Errors']));
	}
	
	public function comment()
	{
		if (App::$forum_user['is_guest'])
			message(App::$lang_common['No permission']);
			
		if (!isset($this->rid))
			message(App::$lang_common['Bad request']);

		if (FALSE === ($cur_rep = $this->reputation->get_by_id($this->rid)))
			message(App::$lang_common['Bad request']);
			
		if ($cur_rep['comment'] != '')
			message(App::$lang['Comment already']);	
		
		if ($cur_rep['user_id'] != App::$forum_user['id'])
			message(App::$lang['Comment other forbidden']);
			
		$errors = array();
			
		if (isset($_POST['form_sent']))
		{
			$message = $this->prepare_message($errors);
			
			if (empty($errors))
			{
				$this->reputation->add_comment($this->rid, $message);
	    		App::$forum_flash->add_info(App::$lang['Comment add redirect']);
				redirect(forum_link(App::$forum_url['reputation_view'], array(App::$forum_user['id'])), App::$lang['Comment add redirect']);			
			} 
		}

		App::$forum_page['crumbs'][] = array(sprintf(App::$lang['User reputation'], forum_htmlencode(App::$forum_user['username'])), forum_link(App::$forum_url['reputation_view'], App::$forum_user['id']));
		
		App::$forum_page['crumbs'][] = App::$lang['Comment header'];
		App::$forum_page['form_action'] = forum_link(App::$forum_url['reputation_comment'], $this->rid);

		View::$instance = View::factory($this->view.'form', array('heading' => App::$lang['Comment for']));
		View::$instance->errors = View::factory($this->view.'errors', array('errors'=>$errors, 'head' => App::$lang['Errors']));
	}
	
	private function add_voice(& $errors, $target, $method)
	{
		$message = $this->prepare_message($errors);
		
		if (empty($errors))
		{
			$this->reputation->add_voice($target, $message, App::$forum_user, $method);
			return TRUE;
		}
		return FALSE;
	}
	
	private function prepare_message(& $errors)
	{
		if (!isset($_POST['req_message']))
			message(App::$lang_common['Bad request']);
			
		$message = forum_linebreaks(forum_trim($_POST['req_message']));

		if ($message == '')
		{
			$errors[] = (App::$lang['No message']);
		}
		else if (strlen($message) > App::$forum_config['o_reputation_maxmessage'])
		{
			$errors[] = sprintf(App::$lang['Too long message'], App::$forum_config['o_reputation_maxmessage']);
		}
		
		if (App::$forum_config['p_message_bbcode'] == '1' || App::$forum_config['o_make_links'] == '1')
		{
			if (!defined('FORUM_PARSER_LOADED'))
			{
				require FORUM_ROOT.'include/parser.php';
			}
			$message = preparse_bbcode($message, $errors);
		}	
		return $message;	
	}
	
	private function pre_process($method)
	{
		if (!isset($this->pid) OR !isset($this->uid))
			message(App::$lang_common['Bad request']);
			
		if (App::$forum_user['is_guest'])
			message(App::$lang_common['No permission']);

		if (App::$forum_user['id'] == $this->uid)
    		message(App::$lang['Silly user']);

		if (($method == 'plus' AND App::$forum_user['g_rep_plus_min'] > App::$forum_user['num_posts']) OR ($method == 'minus' AND App::$forum_user['g_rep_minus_min'] > App::$forum_user['num_posts']))
		{
			message(App::$lang['Small Number of post']);
		}

		$time = App::$now - App::$forum_user['g_rep_timeout']*60;	
	
		if (FALSE === ($target = $this->reputation->get_post_info($this->pid, $this->uid, App::$forum_user['id'], $time)))
			message(App::$lang_common['Bad request']);
			
		if ($target['time'] AND $target['time'] > $time) 
		{
			message(sprintf(App::$lang['Timeout error'],$target['username'],floor(((($target['time'] + App::$forum_user['g_rep_timeout'] * 60) - App::$now) / 60))));
		}

		if ($target['post_id'] AND $this->pid == $target['post_id'])
		{
			message(App::$lang['Error reputation revote']);
		}			
			
		if ($target['rep_enable'] != 1)
			message(App::$lang['User Disable']);
					
		App::$forum_page['crumbs'][] = array(sprintf(App::$lang['Message on topic'],forum_htmlencode($target['subject'])), forum_link(App::$forum_url['post'], $this->pid));
		
		if ($method == 'plus')
		{
			App::$forum_page['crumbs'][] = sprintf(App::$lang['Plus'], forum_htmlencode($target['username']));
		}
		else 
		{
			App::$forum_page['crumbs'][] = sprintf(App::$lang['Minus'], forum_htmlencode($target['username']));
		}
			

		return $target;
	}
	
	private function _check_int_val($val)
	{
		if (!is_numeric($val))
			message(App::$lang_common['Bad request']);
			
		return $val;
	}	
}