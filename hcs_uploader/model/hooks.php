<?php
/**
 *
 * Uploader for PunBB
 * 
 * @copyright (C) 2016 hcs hcs@mail.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * Extension for PunBB (C) 2008-2016 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * 
 * Hooks manager
 */


class Hcs_uploader_Model_Hooks extends Base {
    
    public static function pun_bbcode_pre_buttons_output($Bar)
    {
        if (defined('FORUM_PAGE') && App::$forum_config['uploader_use_bb_button'] == 1 && in_array(FORUM_PAGE, array('viewtopic', 'post', 'postedit')))
        {
            $Bar->add_button(array('name'  => 'upload image', 'title' => App::$lang['Upload button title'], 'tag' => 'img', 'onclick' => 'return true;', 'image' => true));
        }
        
    }
    
    
    
    public static function hd_head()
    {
        if (defined('FORUM_PAGE')) {
            if (FORUM_PAGE == 'viewtopic' || FORUM_PAGE == 'post' || FORUM_PAGE == 'postedit') {
                Hcs_uploader_Model_Uploader::load_modal();
            }
            if (FORUM_PAGE == 'profile-avatar') {
                Hcs_uploader_Model_Uploader::load_inline();
            }
        }
    }
    
    public static function vt_quickpost_pre_fieldset_end($id = 0) 
    {
        $files = array();

        $query = array(
            'SELECT' => '*',
            'FROM' => 'upload_files',
            'ORDER BY' => 'id'
        );        
        
        if ($id == 0) {
            $query['WHERE'] = 'user_id = \''.App::$forum_user['id'].'\' AND resource_name = \'post\' AND resource_id = \'' . 0 . '\'';
        }
        else {
            $query['WHERE'] = 'resource_name = \'post\' AND (resource_id = \'' . $id . '\' OR resource_id = \'' . 0 . '\')';
        }
        
        $query_result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
        
        while ($cur_file = App::$forum_db->fetch_assoc($query_result)) {
            $mime = explode('/', $cur_file['mime']);
            $files[] = array(
                'name'  => $cur_file['orig_name'],
                'size'  => $cur_file['size'],
                'type'  => $cur_file['mime'],
                'file'  => ($mime[0] == 'image') ? forum_link($cur_file['file_path'].'thumbnail/'.$cur_file['name']): forum_link( $cur_file['file_path']. $cur_file['name']),
                'token' => generate_form_token(forum_link(App::$forum_url['uploader_file_delete'], $cur_file['id'])),
                'url'   => forum_link(App::$forum_url['uploader_file_delete'], $cur_file['id']),
                'id'    => $cur_file['id'],
                'thumbnail'  => forum_link($cur_file['file_path'].'thumbnail/'.$cur_file['name'])
            );
        }

        App::$forum_loader->add_js('PUNBB.uploader.ready_files = '.json_encode($files), array('type' => 'inline', 'weight'=> 10));
        
        View::$instance = View::factory(FORUM_ROOT.'extensions/hcs_uploader/view/upload_preview_add');
        echo  View::$instance->render();        
    }
    
    public static function vt_quickpost_pre_fieldset()
    {
        //View::$instance = View::factory(FORUM_ROOT.'extensions/hcs_uploader/view/upload_button', array());
        //echo  View::$instance->render();
    }
}