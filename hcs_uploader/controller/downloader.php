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

class Hcs_uploader_Controller_Downloader extends Controller
{
    
    public function download()
    {
        $file = $this->file_info($this->id);
        $fp = fopen($file['file_path'].$file['name'], 'rb');

        if($fp)
        {
            header('Content-Disposition: attachment; filename="'.$file['orig_name'].'"');
            header('Content-Type: '.$file['mime']);
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Connection: close');
            header('Content-Length: '.$file['size']);

            fpassthru ($fp);
            //readfile($file['file_path'].$file['name']);
            exit;
        }
    } 
 
    private function file_info($id = 0)
    {
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> 'upload_files',
			'WHERE'		=> 'id=\''.$id.'\''
		);
		
		$result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
		return App::$forum_db->fetch_assoc($result);		
    }
}