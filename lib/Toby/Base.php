<?php
namespace Toby;

class Base
{
	public $params,$env,$request,$response,$settings;

	protected $app,$conditions,$routes,$filters,$errors,$middleware;

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
		$this->filters[$where][] = new Route(array("GET","DELETE","HEAD","OPTIONS","PATCH","POST","PUT"),$path,$block,$args);
	}

	public function after($block) { $this->add_filter("after",func_get_args()); }
	public function before($block) { $this->add_filter("before",func_get_args()); }

	public function call($env)
	{
		$this->env = $env;
		$this->request = new \Rackem\Request($env);
		$this->response = ($this->app)? new \Rackem\Response($this->app->call($env)) : new \Rackem\Response();
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
		$args = func_get_args();
		if(!isset($this->settings->configure)) $this->settings->configure = array();
		$environment = (func_num_args() > 1)? array_shift($args) : "all";
		if(!isset($this->settings->configure[$environment])) $this->settings->configure[$environment] = array();
		$this->settings->configure[$environment][] = array_pop($args);
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
		$args = func_get_args();
		$codes = (count($args) > 1)? array_shift($args) : array("all");
		if(!is_array($codes)) $codes = array($codes);
		$block = array_pop($args);
		foreach($codes as $code) $this->errors[$code] = $block;
	}

	public function any($path) { return $this->on(null,func_get_args()); }
	public function get($path) { return $this->on("GET",func_get_args()); }
	public function delete($path) { return $this->on("DELETE",func_get_args()); }
	public function head($path) { return $this->on("HEAD",func_get_args()); }
	public function options($path) { return $this->on("OPTIONS",func_get_args()); }
	public function patch($path) { return $this->on("PATCH",func_get_args()); }
	public function post($path) { return $this->on("POST",func_get_args()); }
	public function put($path) { return $this->on("PUT",func_get_args()); }

	public function on($method, $args)
	{
		$path = array_shift($args);
		$block = array_pop($args);
		return $this->route($method,$path,$block,$args);
	}

	public function flash($key=null,$value=null)
	{
		if(is_null($key)) return $this->flash;
		if(is_null($value)) return isset($this->flash[$key])? $this->flash[$key] : null;
		if(!isset($this->env["rack.session"]["flash"]))
			$this->env["rack.session"]["flash"] = array();
		$this->env["rack.session"]["flash"][$key] = $value;
	}

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
		throw new Halt();
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

	public function is_not_found()
	{
		return $this->status() == 404;
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
		if(isset($this->env['HTTP_VERSION']))
			if($this->env['HTTP_VERSION'] == 'HTTP/1.1' && $this->env['REQUEST_METHOD'] !== 'GET') $status = 303;
		if(!isset($status)) $status = 302;
		
		$this->response->redirect($this->url($uri),$status);
		return $this->halt();
	}

	public function run($with_rackem = true)
	{
		$builder = new \Rackem\Builder($this, $this->middleware);
		if($this->show_exceptions) $builder->use_middleware("\Toby\ShowExceptions");
		if($this->sessions) $builder->use_middleware("\Rackem\Session\Cookie",$this->session_options());
		if($this->csrf) $builder->use_middleware("\Rackem\Protection\Csrf");
		if($this->protection) \Rackem\Protection::protect(is_array($this->protection)? $this->protection : array(), $builder);
		if($this->logging) $builder->use_middleware("\Rackem\RackLogger");
		return $with_rackem? \Rackem::run($builder) : $builder;
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
		elseif( isset($options["disposition"]) && $options["disposition"] == "inline") $this->response->header["Content-Disposition"] = "inline";

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
		if($script_name && !empty($this->env['SCRIPT_NAME'])) $uri[] = $this->env['SCRIPT_NAME'];
		$uri[] = ($address)? $address : $this->request->path_info();
		return implode("/",array_map(function($v) { return ltrim($v,'/'); },$uri));
	}

	public function use_middleware($middleware, $options = array())
	{
		$this->middleware[] = function($app) use ($middleware, $options) {
			return is_object($middleware)? $middleware : new $middleware($app, $options);
		};
	}

	public function __get($prop) { return isset($this->$prop)? $this->settings->$prop : null; }
	public function __isset($prop) { return isset($this->settings->$prop); }
	public function __set($prop,$value) { $this->set($prop,$value); }
	public function __unset($prop) { unset($this->settings->$prop); }

	//template engines
	public function haml($template,$options=array(),$locals=array())
	{
		return $this->render("haml",$template,$options,$locals);
	}

	public function mustache($template,$options=array(),$locals=array())
	{
		return $this->render("mustache",$template,$options,$locals);
	}

	public function php($template,$options=array(),$locals=array())	
	{
		return $this->render("php",$template,$options,$locals);
	}

	public function sass($template,$options=array(),$locals=array())
	{
		return $this->render("sass",$template,$options,$locals);
	}
	
	public function twig($template,$options=array(),$locals=array())
	{
		return $this->render("twig",$template,$options,$locals);
	}

//private

	protected function compile_template($engine,$data,$options,$views)
	{
		$template = $this->find_template($views,$data,$engine);
		if($template)
		{
			$options = isset($this->settings->$engine) ? $this->settings->$engine : array();
			if($engine = Template::engine($engine)) return new $engine($template, $options);
			return new Template($template, $options);
		}
		return $this->halt(500,"Template $data not found.");
	}

	protected function configure_environment()
	{
		if(!isset($this->settings->configure)) return;
		foreach($this->settings->configure as $environment=>$blocks)
		{
			if($environment == "all" || $environment == $this->environment)
				foreach($blocks as $block) $block($this);
		}
	}

	protected function dispatch()
	{
		if($this->method_override && isset($this->params->_method))
			$this->env["REQUEST_METHOD"] = $this->params->_method;
		try
		{
			if($this->static) $this->serve_static();
			$this->process_filter("before");
			$this->routes();
		}catch(Halt $e)
		{
			if($this->is_server_error() || $this->is_client_error())
				return $this->response->send($this->handle_error($e));
			return;
		}catch(\Exception $e)
		{
			return $this->response->send($this->handle_error($e));
		}
		$this->process_filter("after");
	}


	protected function find_template($views,$name,$engine)
	{
		$ext = Template::engine_extension($engine);
		foreach($ext as $possible_ext)
			if(file_exists("$views/$name.$possible_ext")) return "$views/$name.$possible_ext";
		return false;
	}

	protected function handle_error($e)
	{
		$this->response->body = array();
		$this->env['toby.error'] = $e;
		if ($e->status) $this->status($e->status);
		if (!$this->is_client_error() && !$this->is_server_error())
			$this->status(500);

		if($this->is_server_error())
		{
			$handler = new ShowExceptions($this);
			$handler->env = $this->env;
			if($this->dump_errors) $handler->log_exception($this->env, $e);
			if($this->show_exceptions) return $handler->exception_handler($e, false);
		}

		if($this->is_not_found())
		{
			$this->headers(array('X-Cascade'=>'pass'));
			$body = "<h1>Not Found</h1>";
		}

		foreach($this->errors as $code=>$error)
			if($code == $this->response->status || $code == get_class($e))
				return is_callable($error)? $error($this,$e) : $error;
		if(array_key_exists("all",$this->errors) && $default_error = $this->errors["all"])
			return is_callable($default_error)? $default_error($this,$e) : $default_error;
		return array($this->status(),isset($body)? $body : "<h1>Internal Server Error</h1>");
	}

	protected function param_list($keys,$matches)
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

	protected function process_condition($condition,$value)
	{
		if(!array_key_exists($condition, $this->conditions)) return false;
		$block = $this->conditions[$condition];
		return $block($value);
	}

	protected function process_filter($where)
	{
		if(!array_key_exists($where, $this->filters)) return;
		foreach($this->filters[$where] as $route)
		{
			list($pattern,$keys) = $route->compile();
			$this->process_route($pattern,$keys,$route);
		}
	}

	protected function process_route($pattern,$keys,$route)
	{
		$matches = array();
		if(!is_null($route->method) && !in_array($this->request->request_method(),$route->method)) return false;
		if(!preg_match_all($pattern,$this->request_uri(),$matches)) return false;
		foreach($route->conditions as $condition=>$value) if(!$this->process_condition($condition,$value)) return false;
		$params = $this->param_list($keys,array_slice($matches,1));
		foreach($params as $key=>$value) $this->params->$key = $value;
		if($output = $route($this,$params)) $this->response->send($output);
		return $output;
	}

	protected function render($engine,$data,$options=array(),$locals=array(),$block=null)
	{
		$layout = isset($options["layout"])? $options["layout"] : "layout";
		$layout_engine = isset($options["layout_engine"])? $options["layout_engine"] : $engine;
		$locals = array_merge($locals, array("app"=>$this));

		$template = $this->compile_template($engine,$data,$options,$this->settings->views);
		$output = "";
		if($template) $output = $template->render($locals,$block);
		if($layout && is_null($block))
			return $this->render($layout_engine,$layout,$options,$locals,$output);
		return $output;
	}

	protected function request_uri()
	{
		$path = $this->request->path_info();
		// if(isset($this->params->q)) $path = $this->params->q;
		return empty($path)? "/" : $path;
	}

	protected function reset()
	{
		$this->safe_set("environment",isset($_SERVER['RACK_ENV'])? $_SERVER['RACK_ENV'] : "development");
		$this->safe_set("show_exceptions", $this->is_development());
		$this->safe_set("dump_errors", $this->is_development());
		$this->safe_set("logging", $this->is_development());
		$this->safe_set("protection", true);
		$this->safe_set("root", getcwd());
		if(!$this->env) return;
		$this->safe_set("views", "{$this->root}/views");
		$this->safe_set("public_folder", "{$this->root}/public");
		if(isset($this->env["rack.session"]) && isset($this->env["rack.session"]["flash"]))
		{
			$this->set("flash",$this->env["rack.session"]["flash"]);
			$this->env["rack.session"]["flash"] = null;
		}
	}

	protected function route($method,$path,$block,$conditions=array())
	{
		return $this->routes[] = new Route($method,$path,$block,$conditions);
	}

	protected function routes()
	{
		foreach($this->routes as $route)
		{
			list($pattern,$keys) = $route->compile();
			if($this->process_route($pattern,$keys,$route) !== false) return;
		}
		$this->status(404);
		throw new Halt('Not Found');
	}

	protected function safe_set($key, $value)
	{
		if(!isset($this->settings->$key)) $this->set($key,$value);
	}

	protected function serve_static()
	{
		if(!$this->public_folder) return;

		$path = urldecode($this->public_folder.$this->request->path_info());
		if(!is_file($path) || !file_exists($path)) return;

		return $this->send_file($path);
	}
	
	protected function session_options()
	{
		$options = $this->session_secret? array("secret"=>$this->session_secret) : array();
		return is_array($this->sessions)? array_merge($options,$this->sessions) : $options;
	}
}

class Halt extends \ErrorException {}
