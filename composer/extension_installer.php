<?php

class Extension_Installer
{
    function __construct($ext_info)
    {
        $this->ext_info = $ext_info;
    }
    
    function check_access()
    {
        if (isset($_SESSION['composer_passed'])) {
            unset($_SESSION['composer_passed']);
            return false;
        }
        if (! is_writable($this->ext_info['path'].'/composer_cache')) {
            error('Продолжение невозможно. Необходимо установить на папку <strong>'.$this->ext_info['path'].'composer_cache</strong> права 775 и повторите попытку');
        }
        
        if (! is_writable($this->ext_info['path'].'/vendor')) {
            error('Продолжение невозможно. Необходимо установить на папку <strong>extensions/composer/vendor</strong> права 775 и повторите попытку');
        }
        
        return true;
    }

    function load_js()
    {
        global $forum_loader;
        
        if (!defined('PUN_JQUERY_VERSION')) {
            $forum_loader->add_js(
                '//code.jquery.com/jquery-2.1.1.min.js', array('type' => 'url', 'group' => -100 , 'weight' => 75)
            );
        }
        
        $forum_loader->add_js(
            $this->ext_info['url'].'/js/script.js?v31', array('type' => 'url', 'weight'=> 170)
        );
        
        $forum_loader->add_css(
            $this->ext_info['url'].'/css/button-fix.css?v3', array('type' => 'url', 'weight'=> 170)
        );        
    }

    function run_action($action)
    {
        global $forum_user;
        
        if (!$this->check_access())
            return;
        
        $this->load_js();

        extract($GLOBALS, EXTR_REFS);
        
        if (file_exists($this->ext_info['path'].'/lang/'.$forum_user['language'].'.php'))
            require_once $this->ext_info['path'].'/lang/'.$forum_user['language'].'.php';
        else
            require_once $this->ext_info['path'].'/lang/English.php';      

        if ($action == 'install') {
            $form_action = $base_url .'/admin/extensions.php?install='.$id;
            $form_csrf_token = generate_form_token(forum_link('admin/extensions.php?install='.$id));
            $button['value'] = $lang_admin_ext['Install extension'];
            $button['name'] =   'install_comply';            
        }
        else if ($action == 'uninstall') {
            if ($id == 'composer')
            {
                unset($_SESSION['composer_passed']);
                return;
            }
            $form_action = $base_url .'/admin/extensions.php?section=manage&amp;uninstall='.$id;
            $form_csrf_token = generate_form_token($base_url.'/admin/extensions.php?section=manage&amp;uninstall='.$id);
            $button['value'] = $lang_admin_ext['Uninstall'];
            $button['name'] =   'uninstall_comply';
        }
        else {
            $form_action = '';
            $form_csrf_token = '';
        }
       
        require 'installer_console_view.php';
    }
    
}
