<?php
namespace Router;

class Base
{
	public $params,$env,$request,$response;
	
	protected $app,$conditions,$routes,$filters,$settings,$errors,$middleware;
		
	public function __construct($app=null)
	{
		$this->app = $app;
		$this->errors = array();
		$this->filters = array();
		$this->middleware = array();
		$this->settings = new \ArrayObject();
		$this->reset();
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
		$this->reset();
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
		return $this->request->referer();
	}

	public function content_type($type = null, $params = array())
	{
		if(!$type) return $this->response->header["Content-Type"];
		$mime_type = $this->mime_type($type);
		return $this->response->header["Content-Type"] = $mime_type;
	}
	
	public function error()
	{
		$codes = (func_num_args() > 1)? array_shift(func_get_args()) : array("all");
		if(!is_array($codes)) $codes = array($codes);
		$block = array_pop(func_get_args());
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
			elseif(is_array($arg)) $this->response->send($arg);
		}
		if($this->is_server_error() || $this->is_client_error())
			throw new Halt(count($this->response->body)? implode("",$this->response->body) : 'Halt');
		return "";
	}

	public function headers($headers = null)
	{
		if($headers) $this->response->header = array_merge($this->response->header,$headers);
		return $this->response->header;
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

	public function is_development()
	{
		return $this->environment == "development";
	}

	public function is_production()
	{
		return $this->environment == "production";
	}

	public function is_test()
	{
		return $this->environment == "test";
	}
	
	public function not_found($block=null)
	{
		$this->error(404,$block);
	}

	public function logger()
	{
		return $this->request->logger();
	}

	public function mime_type($type,$value=null)
	{
		if(is_null($type) || strpos("/",$type) > -1) return $type;
		if(is_null($value)) return \Rackem\Mime::mime_type($type,null);
		\Rackem\Mime::$mime_types[$type] = $value;
	}
	
	public function pass()
	{
		return false;
	}
	
	public function redirect($uri)
	{
		if($this->env['HTTP_VERSION'] == 'HTTP/1.1' && $this->env['REQUEST_METHOD'] !== 'GET') $status = 303;
		else $status = 302;
		
		$this->response->redirect($this->url($uri),$status);
		return $this->halt();
	}

	public function run($rackem = "\Rackem\Rack")
	{
		if($this->show_exceptions) $rackem::use_middleware("\Router\ShowExceptions");
		if($this->sessions) $rackem::use_middleware("\Rackem\Session\Cookie",$this->session_options());
		foreach($this->middleware as $middleware)
			call_user_func_array("$rackem::use_middleware",$middleware);
		$rackem::run($this);
	}

	public function safe_set($key, $value)
	{
		if(!isset($this->settings->$key)) $this->set($key,$value);
	}
	
	public function set($key,$value)
	{
		$this->settings->$key = $value;
	}
	
	public function enable($key) { $this->set($key,true); }
	public function disable($key) { $this->set($key, false); }
	
	public function status($value=null)
	{
		if(!is_null($value)) $this->response->status = $value;
		return $this->response->status;
	}

	public function attachment($filename)
	{
		$this->response->header["Content-Disposition"] = "attachment";

	}

	public function send_file($path,$options=array())
	{
		if(isset($options["disposition"]) && $options["disposition"] == "attachment" || isset($options["filename"]))
			$this->attachment();
		elseif( $options["disposition"] == "inline") $this->response->header["Content-Disposition"] = "inline";
		
		$file = new \Rackem\File("");
		$file->path = $path;
		$result = $file->serving($this->env);
		$headers = $this->headers();
		$this->headers($result[1]);
		$headers["Content-Length"] = $result[1]["Content-Length"];
		if(isset($options["type"])) $this->content_type($options["type"]);
		if(isset($options["last_modified"])) $this->last_modified($options["last_modified"]);
		return $this->halt(array($result[0],$result[2]));
	}
	
	public function url($address,$absolute = true,$script_name = true)
	{
		$uri = array();
		if($absolute) $uri[] = $this->request->base_url();
		if($script_name) $uri[] = $this->env['SCRIPT_NAME'];
		$uri[] = ($address)? $address : $this->request->path_info();
		return implode("/",array_map(function($v) { return ltrim($v,'/'); },$uri));
	}

	public function use_middleware($args)
	{
		$this->middleware[] = func_get_args();
	}
	
	public function __get($prop) { return isset($this->$prop)? $this->settings->$prop : null; }
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
		return $this->halt(500,"Template $data not found.");
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
			if($this->show_exceptions)
			{
				$handler = new ShowExceptions($this);
				$handler->env = $this->env;
				$handler->exception_handler($e);
			}
			return $this->response->send($this->handle_error($e));
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
				return is_callable($error)? $error($this,$e) : $error;
		if(array_key_exists("all",$this->errors) && $default_error = $this->errors["all"])
			return is_callable($default_error)? $default_error($this,$e) : $default_error;
		return array(500,"<h1>Internal Server Error</h1>");
	}

	private function param_list($keys,$matches)
	{
		$params = array();
		foreach(array_values($keys) as $index=>$key)
		{
			$match = array_shift($matches[$index]);
			if(!isset($params[$key])) $params[$key] = $match;
			elseif(is_array($params[$key])) $params[$key][] = $match;
			else $params[$key] = array($params[$key],$match);
		}
		return $params;
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
		$params = $this->param_list($keys,array_slice($matches,1));
		foreach($params as $key=>$value) $this->params->$key = $value;
		if($output = $route($this,$params)) $this->response->send($output);
		return $output;
	}
	
	private function render($engine,$data,$options=array(),$locals=array(),$block=null)
	{
		//gimme options
		$layout = (isset($options["layout"]))? $options["layout"] : "layout";
		$layout_engine = (isset($options["layout_engine"]))? $options["layout_engine"] : $engine;
		$layout_locals = array("app"=>$this);
		//create template
		$views = $this->settings->views;
		$template = $this->compile_template($engine,$data,$options,$views);
		$output = "";
		if($template) $output = $template->render(array_merge($locals,$layout_locals),$block);
		if($layout && is_null($block))
			return $this->render($layout_engine,$layout,$options,$layout_locals,$output);
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

	private function reset()
	{
		$this->safe_set("environment",isset($_SERVER['RACK_ENV'])? $_SERVER['RACK_ENV'] : "development");
		$this->safe_set("show_exceptions", $this->is_development());
		if(!$this->env) return;
		$this->safe_set("root", dirname($this->env['SCRIPT_FILENAME']));
		$this->safe_set("views", "{$this->root}/views");
		$this->safe_set("public_folder", "{$this->root}/public");
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
		throw new Halt('Not Found');
	}
	
	private function session_options()
	{
		$options = $this->session_secret? array("secret"=>$this->session_secret) : array();
		return is_array($this->sessions)? array_merge($options,$this->sessions) : $options;
	}
}

class Halt extends \ErrorException {}