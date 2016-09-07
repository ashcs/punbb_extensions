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

class Hcs_uploader_Controller_Admin extends Controller
{
    private static $config_fields = array(
		'uploader_basefolder',
		'uploader_watermark_image',
		'uploader_watermark_position',
		'uploader_thumbnail_width',
		'uploader_thumbnail_height',
		'uploader_extensions_allow',
		'uploader_max_upload_once',
		'uploader_max_upload_total',
		'uploader_max_file_size'
    );

    public function __construct($ext_path)
    {
        parent::__construct($ext_path);
        
        App::$forum_page['crumbs'][] = array(
            App::$lang['Uploader'],
            forum_link(App::$forum_url['uploader_admin'])
        );
        App::add_admin_submenu(array(
            'name' => 'uploader_setup',
            'section' => 'uploader',
            'page' => 'admin-uploader-files',
            'href' => HTML::href(array(
                'href' => forum_link(App::$forum_url['uploader_admin_alone']),
                'title' => App::$lang['Uploader alone files']
            ), true)
        ));
        App::add_admin_submenu(array(
            'name' => 'uploader_files',
            'section' => 'uploader',
            'page' => 'admin-uploader-index',
            'href' => HTML::href(array(
                'href' => forum_link(App::$forum_url['uploader_admin']),
                'title' => App::$lang['Uploader Submenu index']
            ), true)
        ));
        
        $this->page = 'admin-uploader-index';        
        $this->section = 'uploader';
    }
    
    public function index()
    {
        $this->page = 'admin-uploader-index';
        View::$instance = View::factory($this->view.'admin/index');        
    }

    public function update()
    {
	foreach (self::$config_fields as $key => $value)
	{
	        $query  = array(
        	    	'UPDATE'	=> 'config',
			'SET'		=> 'conf_value = \''.App::$forum_db->escape(trim($_POST[$value])).'\'',
	            	'WHERE'		=> 'conf_name = \''.App::$forum_db->escape(trim( $value )).'\''
        	);
	        App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
	require_once FORUM_ROOT.'include/cache.php';
	generate_config_cache();

        App::$forum_flash->add_info(App::$lang['Uploader setup update']);
        redirect(forum_link(App::$forum_url['uploader_admin']), App::$lang['uploader bla-bla-bal']);
    }

}