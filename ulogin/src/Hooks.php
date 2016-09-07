<?php
/**
 * Ulogin implemenation
 *
 * @copyright (C) 2016 hcs hcs@mail.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * Extension for PunBB (C) 2008-2016 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace Ulogin;

class Hooks
{

    public static function hd_visit_elements(& $visit_elements2 = null)
    {
        global $forum_user, $ext_info, $forum_loader, $forum_config, $visit_links, $base_url;
  
        if ($forum_user['is_guest'] && isset($ext_info['url']))
        {
            $forum_loader->add_js('//ulogin.ru/js/ulogin.js?v1', array('weight' => 55, 'async' => false, 'group' => FORUM_JS_GROUP_SYSTEM));
            $forum_loader->add_js($ext_info['url'].'/js/ulogin.js?v1', array('weight' => 200));
            $visit_links['ulogin'] = '<div id="uLogin_'.$forum_config['o_ulogin_id'].'" class="ulogin1" data-csrf_token="'.generate_form_token(forum_link('extensions/ulogin/ulogin.php')).'" data-action="'.forum_link('extensions/ulogin/ulogin.php').'" style="padding: 1em 1em;float:left;" data-uloginid="'.$forum_config['o_ulogin_id'].'"></div>';
        }
    }

    public static function rg_start()
    {
        global $forum_config;

        if (empty($GLOBALS['allow_register']) || $GLOBALS['allow_register'] === false) {
            return;
        }

        if (isset($_SESSION['ulogin_data']) && isset($_SESSION['ulogin_data']['group_is_select']) && $_SESSION['ulogin_data']['group_is_select'] == 1) {
           $GLOBALS['allow_register'] = true;
        }
        else {
           $GLOBALS['allow_register'] = false;
        }
        
        if (isset($_SESSION['ulogin_data']) && $forum_config['o_ulogin_force_reg'] == '1' && $GLOBALS['allow_register']) {
            $_POST['form_sent'] = 1;
            $_POST['req_username'] = $_SESSION['ulogin_data']['nickname'];
            $_POST['req_email1'] = $_SESSION['ulogin_data']['email'];
            $_POST['req_password1'] = $_POST['req_password2'] = random_key(8, true);
        }
    }
    
    public static function rg_register_output_start()
    {
        global $forum_user, $ext_info, $forum_loader, $forum_config, $base_url, $forum_page;
    
        if (isset($_SESSION['ulogin_data']))
        {
            if (file_exists('lang/'.$forum_user['language'].'.php'))
                require 'lang/'.$forum_user['language'].'.php';
            else
                require 'lang/English.php';            
            $forum_page['frm_info']['email'] = $ulogin_lang['Register required'];
            $forum_loader->add_js($ext_info['url'].'/js/register.js', array('weight' => 200));
            echo '<script>var ulogin_data = JSON.parse(\''.json_encode($_SESSION['ulogin_data']).'\');</script>';
        }
    }
    
    public static function rg_register_pre_login_redirect($new_uid)
    {
        global $forum_user, $forum_db, $forum_url, $forum_config;
        
        if (!isset($_SESSION['ulogin_data'])) {
            return;
        }
    
        $query = array(
            'INSERT'	=> 'user_id, email, network, identity, uid, nickname, manual, response',
            'INTO'		=> 'ulogin',
            'VALUES'	=> '\''.$new_uid.'\', \''.$forum_db->escape($_SESSION['ulogin_data']['email']).'\', \''.$forum_db->escape($_SESSION['ulogin_data']['network']).'\', \''.$forum_db->escape($_SESSION['ulogin_data']['identity']).'\', \''.$forum_db->escape($_SESSION['ulogin_data']['uid']).'\', \''.$forum_db->escape($_SESSION['ulogin_data']['nickname']).'\', \''.$forum_db->escape($_SESSION['ulogin_data']['manual']).'\', \''.$forum_db->escape(serialize($_SESSION['ulogin_data'])).'\''
        );
            
        $forum_db->query_build($query) or error(__FILE__, __LINE__);
        
        unset($_SESSION['ulogin_data']);
    }    
    
    public static function fn_delete_user_end($user_id)
    {
        global $forum_db;
        // Delete the user
        $query = array(
            'DELETE'	=> 'ulogin',
            'WHERE'		=> 'user_id='.$user_id
        );
        
        ($hook = get_hook('fn_delete_user_qr_delete_ulogin')) ? eval($hook) : null;
        $forum_db->query_build($query) or error(__FILE__, __LINE__);        
    }
    
    public static function li_login_output_start()
    {
        global $forum_user, $ext_info, $forum_loader, $forum_config, $base_url, $errors, $lang_login;
        
        if (isset($_SESSION['ulogin_data']) && empty($errors))
        {
            if (file_exists('lang/'.$forum_user['language'].'.php'))
                require 'lang/'.$forum_user['language'].'.php';
            else
                require 'lang/English.php';
            
            $lang_login['Login errors'] = $ulogin_lang['Confirm required'];
            $errors[] = sprintf($ulogin_lang['Confirm auth info'], $_SESSION['ulogin_data']['network'], $_SESSION['ulogin_data']['email']);
        }
    }
    
    public static function li_login_pre_redirect()
    {
        global $forum_user, $forum_db, $user_id;
        
        if (isset($_SESSION['ulogin_data']))
        {
            $query = array(
                'INSERT'	=> 'user_id, email, network, identity, uid, nickname, manual, response',
                'INTO'		=> 'ulogin',
                'VALUES'	=> '\''.$user_id.'\', \''.$forum_db->escape($_SESSION['ulogin_data']['email']).'\', \''.$forum_db->escape($_SESSION['ulogin_data']['network']).'\', \''.$forum_db->escape($_SESSION['ulogin_data']['identity']).'\', \''.$forum_db->escape($_SESSION['ulogin_data']['uid']).'\', \''.$forum_db->escape($_SESSION['ulogin_data']['nickname']).'\', \''.$forum_db->escape($_SESSION['ulogin_data']['manual']).'\', \''.$forum_db->escape(serialize($_SESSION['ulogin_data'])).'\''
            );
            
            $forum_db->query_build($query) or error(__FILE__, __LINE__);     
                   
            unset($_SESSION['ulogin_data']);
        }
    }
    
    public static function aop_features_gzip_fieldset_end()
    {
        global $forum_user, $forum_page;
        
        if (file_exists('lang/'.$forum_user['language'].'.php'))
            require 'lang/'.$forum_user['language'].'.php';
        else
            require 'lang/English.php';
                
        $forum_page['group_count'] = $forum_page['item_count'] = 0;
        require 'aop_features_gzip_fieldset_end.php';
    }
    
    public static function exchange_register_pre_group()
    {
        if (isset($_SESSION['ulogin_data'])):
        
            $_SESSION['ulogin_data']['group_is_select'] = 1;
            global $forum_user, $forum_page;
        
            if (file_exists('lang/'.$forum_user['language'].'.php'))
                require 'lang/'.$forum_user['language'].'.php';
            else
                require 'lang/English.php';        
        
            $forum_page['frm_info']['email'] = $ulogin_lang['Register required']; 
        ?>
        		<div class="ct-box info-box">
        			<?php echo implode("\n\t\t\t", $forum_page['frm_info'])."\n" ?>
        		</div>
        <?php
        	endif;
    }
    
}