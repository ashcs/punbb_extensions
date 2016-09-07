<?php
/**
 * Uploader for PunBB installer
 * 
 * @author hcs, mrX
 * @copyright (C) 2011-2016 uploader extension for PunBB
 * @copyright Copyright (C) 2008-2016 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package hcs uploader
 */

defined('FORUM_ROOT') or exit('Direct access not allowed');

class Hcs_uploader_Model_Installer {

	private static $config = array(
		'uploader_basefolder'		=> 'uploads/',
		'uploader_thumbnail_path'	=> 'thumbnail/',
		'uploader_watermark_image'	=> 'uploads/watermark.png',
		'uploader_watermark_position'	=> 'bottom left',
		'uploader_thumbnail_width'	=> '90',
		'uploader_thumbnail_height'	=> '90',
		'uploader_extensions_allow'	=> 'jpg,gif,png,doc,xls,txt,zip,rar,pdf',
		'uploader_max_upload_once'	=> '10',
		'uploader_max_upload_total'	=> '100',
		'uploader_max_file_size'	=> '2',
	    'uploader_use_bb_button'	=> '1',
	);

	private static $schema = array(
	   'FIELDS'      => array(
		'id'         => array(
        	    'datatype'     => 'SERIAL',
	            'allow_null'   => false
        	 ),
                'resource_name' => array(
                    'datatype' => 'VARCHAR(25)',
                    'allow_null' => false
                ),
                'resource_id' => array(
                    'datatype' => 'INT(10) UNSIGNED',
                    'allow_null' => false,
                    'default' => '0'
                ),
                'user_id' => array(
                    'datatype' => 'INT(10) UNSIGNED',
                    'allow_null' => false,
                    'default' => '0'
                ),
                'orig_name' => array(
                    'datatype' => 'VARCHAR(120)',
                    'allow_null' => false
                ),
                'name' => array(
                    'datatype' => 'VARCHAR(25)',
                    'allow_null' => false
                ),
                'file_path' => array(
                    'datatype' => 'VARCHAR(255)',
                    'allow_null' => false
                ),
                'mime' => array(
                    'datatype' => 'VARCHAR(25)',
                    'allow_null' => false
                ),
                '`date`' => array(
                    'datatype' => 'INT(10) UNSIGNED',
                    'allow_null' => false,
                    'default' => '0'
                ),
                '`size`' => array(
                    'datatype' => 'INT(10) UNSIGNED',
                    'allow_null' => false,
                    'default' => '0'
                ),
                '`secure`' => array(
                    'datatype' => 'VARCHAR(40)',
                    'allow_null' => false,
                ),

      ),
      'PRIMARY KEY'   => array('id'),
            'INDEXES' => array(
                'res_idx' => array(
                    'resource_name',
                    'resource_id'
                ),
                'user_idx' => array(
                    'user_id'
                ),
   	));
	
	static function install()
	{
		if (!App::$forum_db->table_exists('upload_files')) 
			App::$forum_db->create_table('upload_files', self::$schema);

		foreach (self::$config as $key => $value)
			forum_config_add($key, $value);

		$basefolder = FORUM_ROOT.self::$config['uploader_basefolder'];

		if (!file_exists( $basefolder ))
   			@mkdir($basefolder, 0750) or error(__FILE__, __LINE__);
		@copy($GLOBALS['ext_info']['path'].'/attachments/.htaccess', $basefolder.'/.htaccess') or error(__FILE__, __LINE__);
		@copy($GLOBALS['ext_info']['path'].'/attachments/index.html', $basefolder.'/index.html') or error(__FILE__, __LINE__);
		@copy($GLOBALS['ext_info']['path'].'/attachments/watermark.png', $basefolder.'/watermark.png') or error(__FILE__, __LINE__);
	}


	static function uninstall($cache_path = null)
	{
		//Remove folders?
		forum_config_remove(array_keys(self::$config));
		App::$forum_db->drop_table('upload_files');
	}
	
}