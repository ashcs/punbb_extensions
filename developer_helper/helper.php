<?php
defined('DS') or define('DS', '/' );


class App {

	/*
	 * Global forum vars
	 */
	public static	$lang_common,
	                $lang_admin_common,
					$forum_config,
					$forum_db,
					$forum_user,
					$forum_page,
					$forum_url,
					$autoloading,
					$lang = array(),
					$controller_instance,
					$forum_hooks,
					$forum_flash,
					$forum_loader,
					$base_url,
					$now;

	private static $loaded_lang = array();
	
	private static $_autoload_folders = 'controller|model|view|module';
					
	public static $admin_section = false, $profile_section = false, $is_ajax = false; 
	
	private static $loaded = false;
	
    public static function init()
    {
    	if (self::$loaded)
    		return;
    		
    	global $lang_common, $lang_admin_common, $forum_db, $forum_user, $forum_page, $forum_config, $forum_hooks, $forum_url, $forum_flash, $forum_loader, $base_url;
    	self::$now = time();
        self::$forum_db = & $forum_db;
        self::$forum_user = & $forum_user;
        self::$forum_page = & $forum_page;
        self::$forum_url = & $forum_url;
        self::$forum_hooks = & $forum_hooks;
        self::$forum_config = & $forum_config;
        self::$lang_common = & $lang_common;
        self::$lang_admin_common = & $lang_admin_common;
        self::$forum_flash = & $forum_flash;
        self::$forum_loader = & $forum_loader;
        self::$base_url = & $base_url;
        self::$autoloading['HTML'] = 'extensions'.DS.'developer_helper'.DS.'html.php';
        self::$autoloading['View'] = 'extensions'.DS.'developer_helper'.DS.'view.php';
        self::$autoloading['Controller'] = 'extensions'.DS.'developer_helper'.DS.'controller.php';
        self::$autoloading['Upload'] = 'extensions'.DS.'developer_helper'.DS.'upload.php';
        self::$autoloading['Base'] = 'extensions'.DS.'developer_helper'.DS.'base.php';
        self::$autoloading['Registry'] = 'extensions'.DS.'developer_helper'.DS.'registry.php';
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
        {
        	self::$is_ajax = TRUE;
        }
        spl_autoload_register(array('App','auto_load'));
        self::$loaded = true;
    }	
    
	public static function add_autoload_folder($folder)
	{
		self::$_autoload_folders .= '|'.$folder;
	}
    
    /**
     * Class name must be type Ext_name_Folder_Class_name
     * Or registered (entered in the array $autoloading)
     * Folder name limited to the following list of:
     * controller
     * model
     * view
     * module
     * @param string $class_name
     */
    public static function auto_load($class_name)
	{
		if(isset(self::$autoloading[$class_name]))
		{
			require(FORUM_ROOT.self::$autoloading[$class_name]);
		}
		else
		{
			$path = FORUM_ROOT.'extensions/'.strtolower(preg_replace('/_('. self::$_autoload_folders .')_/i','/$1/',$class_name)).'.php';

			(file_exists($path)) ? require($path) : message("class file $class_name not exist on $path");
		}
	}
	  
 
	/**
	 * Register another autoloader
	 * @param string $callback
	 */
	public static function register_autoloader($callback)
	{
		spl_autoload_unregister(array('App','auto_load'));
		spl_autoload_register($callback);
		spl_autoload_register(array('App','auto_load'));
	}	

	
	public static function route($override_path = null)
	{
		
		if ($override_path == null AND !isset($_GET['r']))
			return false;
			
		if ($override_path == null) {
			$override_path = $_GET['r'];
		}
			
		$params = explode('/',preg_replace('/[^a-zA-Z0-9\-_\/]/','',$override_path));
		foreach ($params as $key => $cur_param)
		{
			if (forum_trim($cur_param) == '')
				message(App::$lang_common['Bad request']);
				//unset ($params[$key]);
		}
		//unset($_GET['r']);
		
        $route['extension'] = array_shift($params);
        $route['controller'] = 'default';
        $route['action'] = 'index';
        $route['arguments'] = array ();
       
        if (count($params) > 1)
        {
            $route['controller'] = array_shift($params);
            $route['action'] = array_shift($params);
            if (count($params) > 0)
                $route['arguments'] = $params;
        }
        else
            $route['controller'] = array_shift($params);
           
/*
 * TODO
 * Check action. If preffixed "_" then to deny access.
 * Or refactoring with replace action calling only with preffix 'action_' on class method
 * 
 *  */            
            
		$controller_name = $route['extension'].'_controller_'.$route['controller'];
		
		self::$controller_instance = new $controller_name (FORUM_ROOT.'extensions'.DS.$route['extension'].DS);
		self::$controller_instance->self_url = App::$base_url.'/extensions/'.$route['extension'];
		
		// self::$controller_instance->attach ( Logger::get_instance(FORUM_CACHE_DIR.'controller_log.txt'));
/*
 * TODO
 * Arguments must be pairs: key->value
 * Need check
 */
		if (!empty($route['arguments']))
		{
			$params_count = count($route['arguments']);
			$i = 0;
			do {
    			self::$controller_instance->__set($route['arguments'][$i], $route['arguments'][++$i]);
			} while (++$i < $params_count - 1);
		}		
		
		
		if (method_exists(self::$controller_instance,$route['action'])) 
			call_user_func(array(self::$controller_instance, $route['action']));
		else 
			message('Invalid action <strong>'. forum_htmlencode($route['action']).'</strong> on controller '.forum_htmlencode($controller_name));	

		defined('FORUM_PAGE') or define('FORUM_PAGE', self::$controller_instance->page );
		defined('FORUM_PAGE_SECTION') or define('FORUM_PAGE_SECTION', self::$controller_instance->section );

		extract($GLOBALS, EXTR_REFS);
				
		if (View::$instance)
		{
			if (View::$forum_override)
			{
				echo  View::$instance->render();
			}
			else 
			{
				require FORUM_ROOT.'header.php';
				ob_start();
				echo  View::$instance->render();
				$tpl_temp = forum_trim(ob_get_contents());
				$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
				ob_end_clean();
				require FORUM_ROOT.'footer.php';
				
			}
		}
		die();		
		
	}

	public static function load_language($namespace) 
	{
		
		if (isset(self::$loaded_lang[$namespace])) 
			return;
			
		$params = explode('.',$namespace);
		
		$path = FORUM_ROOT.'extensions'.DS.$params[0].DS.'lang'.DS.(isset($params[1]) ? self::$forum_user['language'].DS.$params[1] : self::$forum_user['language']).'.php';
		
		if (!file_exists($path))
			$path = str_replace(self::$forum_user['language'],'English', $path);
		
		self::$lang = array_merge(self::$lang, require($path));
		
		self::$loaded_lang[$namespace] = true;
		
	}
	
	
	/**
	 * Add item to admin submenu
	 * 
	 * $item['section']	- FORUM_PAGE_SECTION
	 * $item['page']	- FORUM_PAGE
	 * $item['href'] 	- full url with anchor
	 * $item['name'] 	- extension name
	 * $item['path'] 	- path to extensions 
	 * $item['url'] 	- url to extensions
	 * @param array $item 
	 * 
	 */
	public static function add_admin_submenu ($item)
	{
		$item['code'] = 'if (FORUM_PAGE_SECTION == \''.$item['section'].'\') 
			$forum_page[\'admin_submenu\'][\''.$item['name'].'\'] = \'<li class="\'.((FORUM_PAGE == \''.$item['page'].'\') ? \'active\' : \'normal\').((empty($forum_page[\'admin_submenu\'])) ? \' first-item\' : \'\').\'">'.$item['href'].'</li>\';';

		self::inject_hook('ca_fn_generate_admin_menu_new_sublink', $item);
	
	}
	
	/**
	 * Add item to admin menu
	 * 
	 * $item['section']	- FORUM_PAGE_SECTION
	 * $item['href'] 	- full url with anchor
	 * $item['name'] 	- extension name
	 * $item['path'] 	- path to extensions 
	 * $item['url'] 	- url to extensions
	 * @param array $item 
	 * 
	 */
	public static function add_admin_menu ($item)
	{
		$item['code'] = '$forum_page[\'admin_menu\'][\''.$item['name'].'\'] = \'<li class="\'.((FORUM_PAGE_SECTION == \''.$item['section'].'\') ? \'active\' : \'normal\').((empty($forum_page[\'admin_menu\'])) ? \' first-item\' : \'\').\'">'.$item['href'].'</li>\';';
		self::inject_hook('ca_fn_generate_admin_menu_new_link', $item);
	}


	/**
	 * Add item to profile menu
	 * 
	 * $item['section']	- FORUM_PAGE_SECTION
	 * $item['href'] 	- full url with anchor
	 * $item['name'] 	- extension name
	 * $item['number'] 	- order number
	 * $item['path'] 	- path to extensions 
	 * $item['url'] 	- url to extensions
	 * @param array $item 
	 * 
	 */
	public static function add_profile_menu ($item)
	{
		$item['code'] = '$forum_page[\'main_menu\'][\''.$item['name'].'\'] = \'<li\'.(($section == \''.$item['section'].'\') ? \' class="active"\' : \'\').\'>'.$item['href'].'</li>\';';
		self::inject_hook('pf_change_details_modify_main_menu', $item);
	}
	
	
	/**
	 * Add navigation link item
	 * 
	 * $item['href'] 	- full url with anchor
	 * $item['name'] 	- extension name
	 * $item['id']	 	- id item
	 * $item['page']	- FORUM_PAGE - where active
	 * $item['number'] 	- order number
	 * $item['path'] 	- path to extensions 
	 * $item['url'] 	- url to extensions
	 * @param array $item 
	 * 
	 */
	public static function add_nav_link ($item)
	{
		$item['code'] = '$links[\''.$item['name'].'\'] = \'<li id="'.$item['id'].'"\'.((FORUM_PAGE == \''.$item['page'].'\') ? \' class="isactive"\' : \'\').\'>'.$item['href'].'</li>\';';
		self::inject_hook('fn_generate_navlinks_end', $item);
	}
	
	
	public static function inject_hook ($hook_id,$item)
	{
		global $forum_hooks;
		
		if (!isset($item['path']))
			$item['path'] = '';
		if (!isset($item['url']))
			$item['url'] = '';			
/*
 * TODO
 * $hook_id can be is array
 */
		$hook = '$ext_info_stack[] = array(
				\'id\'                => \''.$item['name'].'\',
				\'path\'            => \''.$item['path'].'\',
				\'url\'            => \''.$item['url'].'\',
				\'dependencies\'    => array ()
			);
			$ext_info = $ext_info_stack[count($ext_info_stack) - 1];

			'.$item['code'].'

			array_pop($ext_info_stack);
			$ext_info = empty($ext_info_stack) ? array() : $ext_info_stack[count($ext_info_stack) - 1];';
		if (isset($forum_hooks[$hook_id]) && !isset($item['priority']))
            array_unshift($forum_hooks[$hook_id],$hook);
		else
		    $forum_hooks[$hook_id][] = $hook;
	}
	
	public static function paginate($total_count, $disp_count, $url, $params = null)
	{
		// Determine the user offset (based on $_GET['p'])
		App::$forum_page['num_pages'] = ceil($total_count / $disp_count);
		App::$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > App::$forum_page['num_pages']) ? 1 : intval($_GET['p']);
		App::$forum_page['start_from'] = $disp_count * (App::$forum_page['page'] - 1);
		App::$forum_page['finish_at'] = min((App::$forum_page['start_from'] + $disp_count), ($total_count));
		App::$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.App::$lang_common['Pages'].'</span> '.paginate(App::$forum_page['num_pages'], App::$forum_page['page'], $url, App::$lang_common['Paging separator'], $params).'</p>';
	}
	
	public static function send_json($params)
	{
		//header('Content-type: text/html; charset=utf-8');
		header('Content-type: application/json; charset=utf-8');
		if (!function_exists('json_encode'))
		{
			function json_encode($data)
			{
				switch ($type = gettype($data))
				{
				    case 'NULL':
				    	return 'null';
				    case 'boolean':
				    	return ($data ? 'true' : 'false');
				    case 'integer':
				    case 'double':
				    case 'float':
				    	return $data;
				    case 'string':
				    	return '"' . addslashes($data) . '"';
				    case 'object':
				    	$data = get_object_vars($data);
				    case 'array':
				    	$output_index_count = 0;
				    	$output_indexed = array();
				    	$output_associative = array();
				    	foreach ($data as $key => $value)
				    	{
				    		$output_indexed[] = json_encode($value);
				    		$output_associative[] = json_encode($key) . ':' . json_encode($value);
				    		if ($output_index_count !== NULL && $output_index_count++ !== $key)
				    		{
				    			$output_index_count = NULL;
				    		}
				    	}
				    	if ($output_index_count !== NULL) {
				    		return '[' . implode(',', $output_indexed) . ']';
				    	} else {
				    		return '{' . implode(',', $output_associative) . '}';
				    	}
				    default:
				    	return ''; // Not supported
				}
			}
		}		
		echo json_encode($params);
		die;
	}
}
	
App::init();	

