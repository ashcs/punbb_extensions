<?php

/**
 *
 * Uploader
 * @copyright (C) 2016 hcs hcs@mail.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * Extension for PunBB (C) 2008-2016 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * 
 * 
 */


class Hcs_uploader_Model_Uploader extends Base
{

    private static $thumbnail_mime = array(
        'image/gif',
        'image/jpeg',
        'image/png'
    );

    public static function load_modal()
    {
        if (isset($GLOBALS['ext_info']['url'])) {
            App::$forum_loader->add_css($GLOBALS['ext_info']['url'].'/css/jquery.filer.css', array('type' => 'url', 'weight'=> 160));
            App::$forum_loader->add_css($GLOBALS['ext_info']['url'].'/css/themes/jquery.filer-dragdropbox-theme.css', array('type' => 'url', 'weight'=> 170));
            App::$forum_loader->add_css($GLOBALS['ext_info']['url'].'/js/vendor/lightbox2/css/lightbox.css', array('type' => 'url', 'weight'=> 170));
        
            App::$forum_loader->add_js(
            $GLOBALS['ext_info']['url'].'/js/vendor/jquery-modal/jquery.modal.js', array('type' => 'url', 'weight'=> 180)
            );
            App::$forum_loader->add_css(
            $GLOBALS['ext_info']['url'].'/js/vendor/jquery-modal/jquery.modal.css', array('type' => 'url', 'weight'=> 180)
            );
        
            $inline = '$("#pun_bbcode_button_upload_image").click(function(){$(".form-group.attach").modal({fadeDuration: 100, modalClass:"modal2"});});';
            App::$forum_loader->add_js($inline,  array('type' => 'inline', 'weight'=> 300));
        }        
    }
    
    public static function load_inline() 
    {
        App::$forum_loader->add_css($GLOBALS['ext_info']['url'].'/css/jquery.filer.css', array('type' => 'url', 'weight'=> 160));
        App::$forum_loader->add_css($GLOBALS['ext_info']['url'].'/css/themes/jquery.filer-dragdropbox-theme.css', array('type' => 'url', 'weight'=> 170));
    }
    
    public static function get_form($resource_name = '', $resource_id = 0)
    {
        App::$forum_loader->add_js($GLOBALS['ext_info']['url'] . '/js/jquery.filer.min.js', array(
            'type' => 'url',
            'weight' => 160
        ));
        
        if ($resource_name != '' && file_exists($GLOBALS['ext_info']['path']. '/js/'.$resource_name.'.uploader.js')) {
            $uploader = $GLOBALS['ext_info']['url'] . '/js/'.$resource_name.'.uploader.js';
        }
        else {
            $uploader = $GLOBALS['ext_info']['url'] . '/js/filer.uploader.js';
        }
        
        App::$forum_loader->add_js($uploader, array('type' => 'url','weight' => 280,));

        App::$forum_loader->add_js('PUNBB.uploader = {}', array('type' => 'inline', 'weight'=> 0));
        
        View::$instance = View::factory(FORUM_ROOT . 'extensions/hcs_uploader/view/upload_form', array(
            'upload_token' => generate_form_token(forum_link(App::$forum_url['uploader_file_upload'])),
            'upload_url' => forum_link(App::$forum_url['uploader_file_upload']),
            //'remove_url' => forum_link(App::$forum_url['uploader_file_remove']),
            'sign'     => generate_form_token($resource_name),
            'resource_name'    => $resource_name,
            //'resource_id'   => $resource_id
        ));
        
        return View::$instance->render();
    }
    
    public static function files_upload()
    {}

    /**
     * FIXME:
     * Происходит выборка по ид ресурса без связи с именем ресурса
     * 
     */
    public static function show_files($resource_name, $id = 0)
    {
        $list = array();
        
        $query = array(
            'SELECT' => '*',
            'FROM' => 'upload_files',
            'WHERE' => 'resource_name = \''.$resource_name.'\' AND resource_id = \'' . $id . '\'',
            'ORDER BY' => 'id'
        );
        $query_result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
        
        while ($rec = App::$forum_db->fetch_assoc($query_result)) {
            $rec['brief_size'] = self::format_size($rec['size']);
            $list[] = $rec;
        }
        
        View::$instance = View::factory($GLOBALS['ext_info']['path'] . '/view/files_list', array(
            "list" => $list,
            "id" => $id
        ));
        return View::$instance->render();
    }
    
    public static function delete($arr = array('id' => 0, 'resource_name' => '', 'resource_id' => 0))
    {
        if ($arr['id']) {
            // delete on id
            $query = array(
                'SELECT' => '*',
                'FROM' => 'upload_files',
                'WHERE' => 'id = \'' . $arr['id'] . '\''
            );
        } else {
            // delete on resource name and id
            $query = array(
                'SELECT' => '*',
                'FROM' => 'upload_files',
                'WHERE' => 'resource_name = \'' . App::$forum_db->escape($arr['resource_name']) . '\' AND resource_id =\'' . $arr['resource_id'] . '\''
            );
        }
        $query_result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
        
        $remove_id = array();
        while ($file = App::$forum_db->fetch_assoc($query_result)) {
            unlink(FORUM_ROOT . $file['file_path'] . $file['name']);
            if (in_array($file['mime'], self::$thumbnail_mime)) {
                @unlink(FORUM_ROOT . $file['file_path'] . App::$forum_config['uploader_thumbnail_path'] . $file['name']);
            }
            $remove_id[] = $file['id'];
        }
        
        if (count($remove_id)) {
            $query = array(
                'DELETE' => 'upload_files',
                'WHERE' => 'id IN (' . implode(',', $remove_id) . ')'
            );
            App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
        }
        ($hook = get_hook('uploader_files_remove')) ? eval($hook) : null;
    }

    public static function set_resource_id($resource_name = '', $resource_id = 0)
    {
        $query = array(
            'UPDATE' => 'upload_files',
            'SET' => 'resource_id = \'' . $resource_id . '\'',
            'WHERE' => 'resource_name = \'' . App::$forum_db->escape($resource_name) . '\' AND user_id = ' . $GLOBALS["forum_user"]["id"] . ' AND resource_id = 0'
        );
        App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
    }

    public static function format_size($size)
    {
        if (! defined('MBYTE'))
            define('MBYTE', 1048576);
        if (! defined('KBYTE'))
            define('KBYTE', 1024);
        
        App::load_language('hcs_uploader');
        
        if ($size >= MBYTE)
            $size = round($size / MBYTE, 2) . App::$lang['Mb'];
        else 
            if ($size >= KBYTE)
                $size = round($size / KBYTE, 2) . App::$lang['Kb'];
            else
                $size = $size . App::$lang['B'];
        
        return $size;
    }

    public static function file_info($id = 0)
    {
        $query = array(
            'SELECT' => '*',
            'FROM' => 'upload_files',
            'WHERE' => 'id=\'' . $id . '\''
        );
        
        $result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
        return App::$forum_db->fetch_assoc($result);
    }
}
