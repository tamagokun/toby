<?php
namespace Router;

class Base
{
	public $params,$env,$request,$response;
	
	protected $app,$conditions,$routes,$filters,$settings,$errors;
		
	public function __construct($app=null)
	{
		$this->app = $app;
		$this->errors = array();
		$this->filters = array();
		$this->settings = (object) array();
	}
	
	public function add_filter($where,$args)
	{
		$block = array_pop($args);
		$path = (isset($args[0]) && is_string($args[0]))? array_shift($args) : "*";
		if(!isset($this->filters[$where])) $this->filters[$where] = array();
		$this->filters[$where][] = new Route(null,$path,$block,$args);
	}
	
	public function after($block)
	{
		$this->add_filter("after",func_get_args());
	}
	
	public function before($block)
	{
		$this->add_filter("before",func_get_args());
	}
	
	public function call($env)
	{
		$this->env = $env;
		$this->request = new \Rackem\Request($env);
		$this->response = ($this->app)? new \Rack\Response($this->app->call($env)) : new \Rackem\Response();
		$this->params = (object) $this->request->params();
		$this->settings = (object) $this->defaults();
		$this->configure_environment();
		$this->dispatch();
		return $this->response->finish();
	}
	
	public function condition($name,$block)
	{
		if(!isset($this->conditions)) $this->conditions = array();
		$this->conditions[$name] = $block;
	}
	
	public function configure($block)
	{
		if(!isset($this->settings->configure)) $this->settings->configure = array();
		$environment = (func_num_args() > 1)? array_shift(func_get_args()) : "all";
		if(!isset($this->settings->configure[$environment])) $this->settings->configure[$environment] = array();
		$this->settings->configure[$environment][] = array_pop(func_get_args());
	}
	
	public function back()
	{
		//return $this->request->referer();
	}
	
	public function error($codes, $block=null)
	{
		$codes = is_array($codes)? $codes : array($codes);
		foreach($codes as $code) $this->errors[$code] = $block;
	}
	
	public function get($path) { $this->add_route("GET",func_get_args()); }
	public function delete($path) { $this->add_route("DELETE",func_get_args()); }
	public function head($path) { $this->add_route("HEAD",func_get_args()); }
	public function options($path) { $this->add_route("OPTIONS",func_get_args()); }
	public function patch($path) { $this->add_route("PATCH",func_get_args()); }
	public function post($path) { $this->add_route("POST",func_get_args()); }
	public function put($path) { $this->add_route("PUT",func_get_args()); }
	
	public function halt()
	{
		foreach(func_get_args() as $arg)
		{
			if(is_int($arg)) $this->status($arg);
			elseif(is_string($arg)) $this->response->write($arg);
			elseif(is_callable($arg)) $this->response->write($arg($this));
		}
		if($this->is_server_error() || $this->is_client_error()) throw new \Exception('Halt');
		return "";
	}
	
	public function is_informational()
	{
		return $this->status() >= 100 && $this->status() <= 199;
	}
	
	public function is_success()
	{
		return $this->status() >= 200 && $this->status() <= 299;
	}
	
	public function is_redirect()
	{
		return $this->status() >= 300 && $this->status() <= 399;
	}
	
	public function is_client_error()
	{
		return $this->status() >= 400 && $this->status() <= 499;
	}
	
	public function is_server_error()
	{
		return $this->status() >= 500 && $this->status() <= 599;
	}
	
	public function not_found($block=null)
	{
		$this->error(404,$block);
	}
	
	public function pass()
	{
		return false;
	}
	
	public function redirect($uri)
	{
		if($this->env['HTTP_VERSION'] == 'HTTP/1.1' && $this->env['REQUEST_METHOD'] !== 'GET') $status = 303;
		else $status = 302;
		
		$this->response->redirect($this->uri($uri),$status);
		return $this->halt();
	}
	
	public function set($key,$value)
	{
		$this->settings->$key = $value;
	}
	
	public function status($value=null)
	{
		if(!is_null($value)) $this->response->status = $value;
		return $this->response->status;
	}
	
	public function uri($address,$absolute = true,$script_name = true)
	{
		$uri = array();
		if($absolute) $uri[] = $this->request->base_url();
		if($script_name) $uri[] = $this->env['SCRIPT_NAME'];
		$uri[] = ($address)? $address : $this->request->path_info();
		return implode("/",array_map(function($v) { return ltrim($v,'/'); },$uri));
	}
	
	public function __get($prop) { return (isset($this->$prop))? $this->settings->$prop : null; }
	public function __isset($prop) { return isset($this->settings->$prop); }
	public function __set($prop,$value) { $this->set($prop,$value); }
	public function __unset($prop) { unset($this->settings->$prop); }
	
	//template engines
	public function php($template,$options=array(),$locals=array())	
	{
		return $this->render("php",$template,$options,$locals);
	}
	
	//private
	private function add_route($method, $args)
	{
		$path = array_shift($args);
		$block = array_pop($args);
		$this->route($method,$path,$block,$args);
	}
	
	private function compile_template($engine,$data,$options,$views)
	{
		$template = $this->find_template($views,$data,$engine);
		if($template) return new Template($template);
		return false;	//500 no template
	}
	
	private function configure_environment()
	{
		if(!isset($this->settings->configure)) return;
		foreach($this->settings->configure as $environment=>$blocks)
		{
			if($environment == "all" || $environment == $this->environment)
				foreach($blocks as $block) $block($this);
		}
	}
	
	private function defaults()
	{
		$root = dirname($this->env['SCRIPT_FILENAME']);
		return array_merge(array(
			"root" => $root,
			"views" => "$root/views",
			"public_folder" => "$root/public",
			"environment" => ($this->env['RACK_ENV'])? $this->env['RACK_ENV'] : "development"
		),(array)$this->settings);
	}
	
	private function dispatch()
	{
		try
		{
			//if( settings->static )
			$this->filters("before");
			$this->routes();
		}catch(\Exception $e)
		{
			ob_end_clean();
			return $this->response->write($this->handle_error($e));
		}
		$this->filters("after");
	}
	
	private function filters($where)
	{
		foreach($this->filters as $key=>$filter)
		{
			if($where !== $key) continue;
			foreach($filter as $route)
			{
				list($pattern,$keys) = $route->compile();
				$this->process_route($pattern,$keys,$route);
			}
		}
	}
	
	private function find_template($views,$name,$engine)
	{
		$ext = Template::engine_extension($engine);
		foreach($ext as $possible_ext)
			if(file_exists("$views/$name.$possible_ext")) return "$views/$name.$possible_ext";
		return false;
	}
	
	private function handle_error($e)
	{
		$this->env['router.error'] = $e;
		foreach($this->errors as $code=>$error)
			if($code == $this->response->status || $code == get_class($e))
				return is_callable($error)? $error($this) : $error;
		return "";
	}
	
	private function process_condition($condition,$value)
	{
		if(array_key_exists($condition,$this->conditions)){
			$block = $this->conditions[$condition];
			return $block($value);
		}
		return false;
	}
	
	private function process_route($pattern,$keys,$route)
	{
		$matches = array();
		if(!is_null($route->method) && $route->method != $this->request->request_method()) return false;
		if(!preg_match_all($pattern,$this->request_uri(),$matches)) return false;
		foreach($route->conditions as $condition=>$value) if(!$this->process_condition($condition,$value)) return false;
		$params = array_combine($keys,array_map(function($match) {return array_shift($match);},array_slice($matches,1)));
		foreach($params as $key=>$value) $this->params->$key = $value;
		if($output = $route($this)) $this->response->send($output);
		return $output;
	}
	
	private function render($engine,$data,$options=array(),$locals=array(),$block=null)
	{
		//gimme options
		$layout = (isset($options["layout"]))? $options["layout"] : "layout";
		$layout_engine = (isset($options["layout_engine"]))? $options["layout_engine"] : $engine;
		//create template
		$views = $this->settings->views;
		$template = $this->compile_template($engine,$data,$options,$views);
		$output = $block;
		if($template) $output = $template->render($locals,$block);
		if($layout && is_null($block))
			return $this->render($layout_engine,$layout,$options,$locals,$output);
		return $output;
	}
	
	private function request_uri()
	{
		$path = $this->request->path_info();
		//TODO: find a better way to handle old web servers
		if(isset($this->params->q)) $path = $this->params->q;
		if(empty($path)) $path = "/";
		return $path;
	}
	
	private function route($method,$path,$block,$conditions=array())
	{
		$this->routes[] = new Route($method,$path,$block,$conditions);
	}
	
	private function routes()
	{
		foreach($this->routes as $route)
		{
			list($pattern,$keys) = $route->compile();
			if($this->process_route($pattern,$keys,$route) !== false) return;
		}
		$this->status(404);
		throw new \Exception('Not Found');
	}
}