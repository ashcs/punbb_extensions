<?php

class Controller extends Base
{
	
	public $layout;
	public $path;
	protected $real_path;
	public $view;
	
	
	public $page = 'default-page';
	
	public $section = 'default-section';
	
	private $filters = array('id' => 'int');
	
	private $is_filter = array(
		'int' 		=>	'is_numeric',
		'bool' 		=>	'is_bool',
		'float'		=>	'is_float',
		'string'	=>	'is_string',
		'array'		=>	'is_array',
		'object'	=>	'is_object'
	);
			
	public function __construct($ext_path)
	{
		$this->path = $ext_path;
		$this->view = $ext_path . 'view' . DIRECTORY_SEPARATOR;
		// Reset forum_page counters
		App::$forum_page['group_count'] = App::$forum_page['item_count'] = App::$forum_page['fld_count'] = 0;

	}
	
	function __set($key, $value)
	{
		if (isset($this->filters[$key]) AND !$this->is_filter[$this->filters[$key]]($value))
			 message(App::$lang_common['Bad request']. ' Passed parameter is invalid');
			 
		parent::__set($key, $value);
	}
	
	function set_filter($key, $filter = null)
	{   
		if (is_array($key)) 
		{
			foreach ($key as $cur_key => $cur_filter)
			{
				$this->filters[$cur_key] = $cur_filter;
			}
		}
		else
		{
			$this->filters[$key] = $filter;
		}
	}
	
	protected function _check_csrf_token($generated_token)
	{
		if (!isset($_POST['csrf_token']) && (!isset($this->csrf_token) || $this->csrf_token !== $generated_token))
		{
			csrf_confirm_form();
		}		
	}
	
	protected function error($msg, $header, $type = 'warning')
	{
	    return array(
	        'view' => (isset($this->view) ? $this->view.'errors' :  null),
	        'name' => 'errors',
	        'data' => array(
	            'errors' => array(
	                $msg,
	            ),
	            'head' => $header,
	            'type' => $type
	        )
	    );
	}
	
	protected function process_ajax()
	{
	    $result = $this->process_post();
	    if (!empty($result['data'])) {
	        send_json($result['data']);
	    }
	    else {
	        send_json(array('code' => 0));
	    }
	    exit;
	}
	
	protected function process_post()
	{
	    if (!isset($_POST['form_action']))
	    {
	        return;
	    }
	
	    $method = 'post_'.forum_htmlencode($_POST['form_action']);
	
	    if (is_callable(array($this, $method)))
	    {
	        return $this->{$method}();
	    }
	    else {
	        echo 'not is callable '. get_class($this). ' ' . $method;
	    }
	
	}
}