<?php
namespace Router;

class Base
{
	public $params;
	
	protected $app,$conditions,$routes,$filters;
	protected $errors;
		
	public function __construct($app=null)
	{
		$this->app = $app;
	}
	
	public function call($env)
	{
		$this->env = $env;
		$this->request = new \Rackem\Request($env);
		$this->response = ($this->app)? new \Rack\Response($this->app->call($env)) : new \Rackem\Response();
		$this->params = (object) $this->request->params();
		$this->dispatch();
		return $this->response->finish();
	}
	
	public function get($path)
	{
		$options = array_slice(func_get_args(),1);
		$block = array_pop($options);
		$this->route("GET",$path,$block,$options);
	}
	
	public function halt()
	{
		switch(func_num_args())
		{
			case 1:
				$arg = array_shift(func_get_args());
				if(is_int($arg)) $this->response->status = $arg;
				elseif(is_string($arg)) $this->response->write($arg);
				elseif(is_callable($arg)) $this->response->write($arg($this));
				break;
			case 2:
				$this->response->status = array_shift(func_get_args());
				$this->response->write(array_pop(func_get_args()));
				break;
		}
		return true;
	}
	
	public function pass()
	{
		return false;
	}
	
	public function php($template,$options=array(),$locals=array())	
	{
		return $this->render("php",$template,$options,$locals);
	}
	
	//private
	private function compile_template($engine,$data,$options,$views)
	{
		$template = $this->find_template($views,$data,$engine);
		if($template) return new Template($template);
		return false;	//500 no template
	}
	
	private function dispatch()
	{
		try
		{
			//if( settings->static )
			$this->filters("before");
			$this->routes();
		}catch(Exception $e)
		{
			
		}
		$this->filters("after");
	}
	
	private function filters($where)
	{
		
	}
	
	private function find_template($views,$name,$engine)
	{
		$ext = Template::engine_extension($engine);
		foreach($ext as $possible_ext)
			if(file_exists("$views/$name.$possible_ext")) return "$views/$name.$possible_ext";
		return false;
	}
	
	private function process_route($pattern,$keys,$route)
	{
		$matches = array();
		if(!preg_match_all($pattern,$this->request_uri(),$matches)) return false;		
		$params = array_combine($keys,array_map(function($match) {return array_shift($match);},array_slice($matches,1)));
		foreach($params as $key=>$value) $this->params->$key = $value;
		if($output = $route($this)) $this->response->write($output);
		return $output;
	}
	
	private function render($engine,$data,$options=array(),$locals=array(),$block=null)
	{
		//gimme options
		$layout = (isset($options["layout"]))? $options["layout"] : "layout";
		$layout_engine = (isset($options["layout_engine"]))? $options["layout_engine"] : $engine;
		//create template
		$views = __DIR__."/../../test/views";	//settings.views
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
	
	private function route($method,$path,$block,$options=array())
	{
		$this->routes[] = new Route($method,$path,$block,$options);
	}
	
	private function routes()
	{
		foreach($this->routes as $route)
		{
			list($pattern,$keys) = $route->compile();
			if($this->process_route($pattern,$keys,$route) !== false) return;
		}
	}
}