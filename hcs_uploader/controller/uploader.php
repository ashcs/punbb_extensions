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

require FORUM_ROOT . './extensions/hcs_uploader/class.uploader.php';

require FORUM_ROOT . './extensions/hcs_uploader/src/abeautifulsite/SimpleImage.php';
use abeautifulsite\SimpleImage;

class Hcs_uploader_Controller_Uploader extends Controller
{

    private static $thumbnail_mime = array(
        'image/gif',
        'image/jpeg',
        'image/png'
    );

    public function upload()
    {
        $handle = new Uploader($_FILES['files']);
        $user_id = intval($GLOBALS["forum_user"]["id"]);
        
        if ($user_id < 2) {
            return; // not registered user
        }

        $params = $this->get_default_params($user_id);
        $resource_name =  (isset($_POST['resource_name'])) ? forum_trim($_POST['resource_name']) : '';

        if (method_exists($this, 'get_'.$resource_name.'_params')) {
            $this->{'get_'.$resource_name.'_params'}($params, $user_id);
        }
        else {
            ($hook = get_hook('get_uploader_params')) ? eval($hook) : null;
        }

        $data = $handle->upload($_FILES['files'], $params);
        
        if ($data['isSuccess']) {
            if (isset($params['processCallback']) && is_callable($params['processCallback'])) {
                call_user_func_array($params['processCallback'], array('params'=>$params, 'data'=>$data, 'resource_name'=>$resource_name));
            }
            else {
                $this->process_default($params, $data, $resource_name);
            }
        }
        
        if ($data['hasErrors']) {
            header('Content-type: application/json; charset=utf-8');
            echo json_encode(array(
                'result'    => -1,
                'errors'    => $data['errors']
            ));
            exit;
        }

        function onFilesRemoveCallback($removed_files)
        {
            /*
             * foreach($removed_files as $key=>$value){ $file = './uploads/' . $value; if(file_exists($file)){ unlink($file); } }
             */
            return $removed_files;
        }
        
    }

    function onDefaultError($errors, $b)
    {
        header('Content-type: application/json; charset=utf-8');
        echo json_encode(array('result' => -1, 'error' => implode(',', $errors)));
        exit;        
    }
    
    public function delete()
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(get_current_url())) {
            csrf_confirm_form();
            exit;
        }
        
        if (!isset($this->id)) {
            message(App::$lang_common['Bad request']);
            exit;
        }
        
        $query = array(
            'SELECT' => '*',
            'FROM' => 'upload_files',
            'WHERE' => 'id = \'' . App::$forum_db->escape($this->id) . '\''
        );
        
        $result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);

        if (NULL != ($file = App::$forum_db->fetch_assoc($result))) {
            if ($file['user_id'] == App::$forum_user['id'] || App::$forum_user['g_id'] == FORUM_ADMIN) {
                $query = array(
                    'DELETE' => 'upload_files',
                    'WHERE' => 'id = \'' . App::$forum_db->escape($this->id) . '\''
                );
                App::$forum_db->query_build($query) or error(__FILE__, __LINE__);

                unlink( FORUM_ROOT.$file['file_path'].$file['name'] );
                if (in_array($file['name'], self::$thumbnail_mime)) {
                    unlink( FORUM_ROOT.$file['file_path'].App::$forum_config['uploader_thumbnail_path'].$file['name'] );
                }
                
                header('Content-type: application/json; charset=utf-8');
                echo json_encode(array('status' => 0, 'message' => 'file deleted'));
                exit;                
            }
        }       
      
        header('Content-type: application/json; charset=utf-8');
        echo json_encode(array('status' => 0, 'message' => 'file not found'));
        exit;
    }
    
    public static function file_info($orig_name = '')
    {
        $query = array(
            'SELECT' => '*',
            'FROM' => 'upload_files',
            'WHERE' => 'orig_name = \'' . App::$forum_db->escape($orig_name) . '\' AND resource_name =\'\' AND resource_id = 0'
        );
        
        $result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
        return App::$forum_db->fetch_assoc($result);
    }
    
    private function get_default_params($user_id)
    {
        return array(
            'limit' => App::$forum_config['uploader_max_upload_once'], // Maximum Limit of files. {null, Number}
            'maxSize' => App::$forum_config['uploader_max_file_size'], // Maximum Size of files {null, Number(in MB's)}
            'extensions' => explode(',', App::$forum_config['uploader_extensions_allow']), // Whitelist for file extension. {null, Array(ex: array('jpg', 'png'))}
            'required' => false, // Minimum one file is required for upload {Boolean}
            'uploadDir' => App::$forum_config['uploader_basefolder'] . $user_id . '/', // Upload directory to user_id folder {String}
            'title' => array(
                'auto',
                20
            ), // New file name {null, String, Array} *please read documentation in README.md
            'removeFiles' => true, // Enable file exclusion {Boolean(extra for jQuery.filer), String($_POST field name containing json data with file names)}
            'perms' => 660, // Uploaded file permisions {null, Number}
            'onCheck' => null, // A callback function name to be called by checking a file for errors (must return an array) | ($file) | Callback
            'onError' => array($this, 'onDefaultError'), // A callback function name to be called if an error occured (must return an array) | ($errors, $file) | Callback
            'onSuccess' => null, // A callback function name to be called if all files were successfully uploaded | ($files, $metas) | Callback
            'onUpload' => null, // A callback function name to be called if all files were successfully uploaded (must return an array) | ($file) | Callback
            'onComplete' => null, // A callback function name to be called when upload is complete | ($file) | Callback
            'onRemove' => 'onFilesRemoveCallback' // A callback function name to be called by removing files (must return an array) | ($removed_files) | Callback
        );
    } 
    
    private function get_avatar_params(& $params, $user_id)
    {
        //$params['uploadDir'] = App::$forum_config['uploader_basefolder'] . $user_id . '/'; 
        $params['extensions'] = array ('gif','jpg','png');
        $params['processCallback'] = array($this, 'process_avatar');
        $params['user_id']  = $user_id;
    }
    
    function process_avatar($params, $data, $resource_name)
    {
        
        $files = $data['data']['files'][0];
        $metas = $data['data']['metas'][0];
        
        list($width, $height, $type,) = @/**/getimagesize($params['uploadDir'] . $metas['name']);
        if (empty($width) || empty($height) || $width > App::$forum_config['o_avatars_width'] || $height > App::$forum_config['o_avatars_height']) {

            $img = new SimpleImage();
            $img->load($params['uploadDir'] . $metas['name']);
            
            if ($width > $height) {
                $img->fit_to_width(App::$forum_config['o_avatars_width']);
            }
            else {
                $img->fit_to_height(App::$forum_config['o_avatars_height']);
            }
            
            $img->save(App::$forum_config['o_avatars_dir'].'/'.$params['user_id'].'.'.$metas['extension']);
            $width = App::$forum_config['o_avatars_width'];
            $height = App::$forum_config['o_avatars_height'];
        }
        else {
            rename($params['uploadDir'] . $metas['name'], App::$forum_config['o_avatars_dir'].'/'.$params['user_id'].'.'.$metas['extension']);
        }  
        //unlink($params['uploadDir'] . $metas['name']);
        //rename(FORUM_ROOT.$params['uploadDir'] . $metas['name'], App::$forum_config['o_avatars_dir'].'/'.$params['user_id'].'.'.$metas['extension']);
        //chmod(App::$forum_config['o_avatars_dir'].'/'.$params['user_id'].'.'.$metas['extension'], 0644);
        
        // Avatar
        
        $avatar_width = (intval($width) > 0) ? intval($width) : 0;
        $avatar_height = (intval($height) > 0) ? intval($height) : 0;
        
        $avatar_type = FORUM_AVATAR_NONE;
        if ($metas['extension'] == 'gif')
        {
            $avatar_type = FORUM_AVATAR_GIF;
        }
        else if ($metas['extension'] == 'jpg')
        {
            $avatar_type = FORUM_AVATAR_JPG;
        }
        else if ($metas['extension'] == 'png')
        {
            $avatar_type = FORUM_AVATAR_PNG;
        }        
        
        // Save to DB
        $query = array(
            'UPDATE'	=> 'users',
            'SET'		=> 'avatar=\''.$avatar_type.'\', avatar_height=\''.$avatar_height.'\', avatar_width=\''.$avatar_width.'\'',
            'WHERE'		=> 'id='.$params['user_id']
        );
        //($hook = get_hook('pf_change_details_avatar_qr_update_avatar')) ? eval($hook) : null;
        App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
        
        
        
        header('Content-type: application/json; charset=utf-8');
        echo json_encode(array('result' => 0));
        exit;        
    }
    
    private function process_default($params, $data, $resource_name)
    {
        
        // Calc count files per user
        $query = array(
            'SELECT' => 'COUNT(*) AS cnt',
            'FROM' => 'upload_files',
            'WHERE' => 'user_id = \'' . $GLOBALS["forum_user"]["id"] . '\''
        );
        $result = App::$forum_db->query_build($query) or error(__FILE__, __LINE__);
        $res = App::$forum_db->fetch_assoc($result);
        $count_files = $res['cnt'];
        
        $files = $data['data']['files'];
        $metas = $data['data']['metas'];
        
        foreach ($files as $key => $val) {
            $count_files ++;
            if ($count_files > App::$forum_config['uploader_max_upload_total']) {
                header('Content-type: application/json; charset=utf-8');
                echo json_encode(array('result' => -1, 'message' => 'uploader_max_upload_total'));
                exit;
            }
        
            $orig_name = $metas[$key]['old_name'];// . '.' . $metas[$key]['extension'];
            $ar = explode('/', $files[$key]);
        
            $mime = '';
            if (is_array($metas[$key]['type']))
                $mime = implode('/', $metas[$key]['type']);
        
            $attach_record = array(
                'user_id' => $GLOBALS["forum_user"]["id"],
                'resource_name' =>  '\'' . App::$forum_db->escape($resource_name). '\'',
                //'resource_id' =>  '\'' . App::$forum_db->escape($resource_id). '\'',
                'orig_name' => '\'' . App::$forum_db->escape($orig_name) . '\'',
                'size' => '\'' . $metas[$key]['size'] . '\'',
                'date' => '\'' . time() . '\'',
                'mime' => '\'' . App::$forum_db->escape($mime) . '\'',
                'name' => '\'' . App::$forum_db->escape($metas[$key]['name']) . '\'',
                'file_path' => '\'' . App::$forum_db->escape($params['uploadDir']) . '\'',
                'secure' => '\'' . $_POST['csrf_token'] . '\''
            );
            $attach_query = array(
                'INSERT' => implode(',', array_keys($attach_record)),
                'INTO' => 'upload_files',
                'VALUES' => implode(',', array_values($attach_record))
            );
            App::$forum_db->query_build($attach_query) or error(__FILE__, __LINE__);
            ($hook = get_hook('uploader_insert_file')) ? eval($hook) : null;
        
            $file_id = App::$forum_db->insert_id();
        
            $path = '';
        
            if (in_array($mime, self::$thumbnail_mime)) {
                // Make thumbnail only for image mime type
                $path = $params['uploadDir'] . App::$forum_config['uploader_thumbnail_path'];
                if (! is_dir($path)) {
                    mkdir($path, 750);
                }
        
                try {
                    $img = new SimpleImage();
        
                    $img->load($params['uploadDir'] . '/' . $metas[$key]['name'])
                    ->thumbnail(App::$forum_config['uploader_thumbnail_width'], App::$forum_config['uploader_thumbnail_height'])
                    ->save($path . $metas[$key]['name']);
                    if (App::$forum_config['uploader_watermark_image'])
                        $img->load($params['uploadDir'] . '/' . $metas[$key]['name'])
                        ->overlay(App::$forum_config['uploader_watermark_image'], App::$forum_config['uploader_watermark_position'], .8)
                        ->save($params['uploadDir'] . $metas[$key]['name']);
                } catch (Exception $e) {}
            }
        }

        header('Content-type: application/json; charset=utf-8');
        echo json_encode(array(
            'result'    => 0,
            'file_id'   => $file_id,
            'token'     => generate_form_token(forum_link(App::$forum_url['uploader_file_delete'], $file_id)),
            'url'       => forum_link(App::$forum_url['uploader_file_delete'], $file_id),
            'thumbnail'       => forum_link($path. $metas[$key]['name']),
        ));
        exit;
        
        
        return array(
            'file_id'   => $file_id,
            'thumbnail_path' => $path. $metas[$key]['name']
        );
    }
}


