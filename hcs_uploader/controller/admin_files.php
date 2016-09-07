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
 * Admin Controller
 */

class Hcs_uploader_Controller_Admin_Files extends Controller
{
    
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
        
        $this->page = 'admin-uploader-files';        
        $this->section = 'uploader';
    }
    

    public function files_list()
    {
        $query = array(
            'SELECT'	=> 'uf.*, u.username AS username',
            'FROM'		=> 'upload_files AS uf',
            'JOINS'		=> array(
                array(
                    'LEFT JOIN'	=> 'users AS u',
                    'ON'	=> 'uf.user_id = u.id'
                )),
            'WHERE'		=> 'uf.resource_id = 0',
        );
        $result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
        
        $files = array();
        while ($rec = App::$forum_db->fetch_assoc($result))
        {
            $files[] = $rec;
        }
                
        View::$instance = View::factory($this->view.'admin/files_list', array(
        	'files'   => $files
        ));
    }
    
    public function delete()
    {
	$del = $_POST['del'];
	if(is_array($del)) {
	        $query = array(
        	    'SELECT'	=> '*',
	            'FROM'	=> 'upload_files',
        	    'WHERE'     => 'id IN ('. implode(",", $del).')'
	        );
        	$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
	        while ($file = App::$forum_db->fetch_assoc($result))
        	{
			$query = array(
				'DELETE'	=>	'upload_files',
				'WHERE'		=>	'id = \''.App::$forum_db->escape($file['id']).'\''
			);
			App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
			@unlink( FORUM_ROOT.$file['file_path'].$file['name'] );
        	}
	}
        redirect(forum_link(App::$forum_url['uploader_admin_alone']), App::$lang['uploader bla-bla-bal']);

    }
}