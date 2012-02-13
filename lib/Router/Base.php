<?php
namespace Router;

class Base
{
	protected $app;
	protected $conditions;
	protected $routes;
	protected $filters;
	protected $errors;
	protected $params;
	
	public function __construct($app=null)
	{
		$this->app = $app;
	}
	
	public function call($env)
	{
		$this->env = $env;
		$this->request = new \Rackem\Request($env);
		$this->response = ($this->app)? new \Rack\Response($this->app->call($env)) : new \Rackem\Response();
		$this->params = $this->request->params();
		$this->dispatch();
		return $this->response->finish();
	}
	
	public function get($path)
	{
		$options = array_slice(func_get_args(),1);
		$block = array_pop($options);
		$this->route("GET",$path,$block,$options);
	}
	
	//private
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
	
	private function process_route($pattern,$keys,$route)
	{
		$matches = array();
		if(!preg_match($pattern,$this->request_uri(),$matches)) return false;
		//extract(array_combine($keys,array_shift($matches)));
		$route(array_slice($matches,1));
	}
	
	private function request_uri()
	{
		$path = $this->request->path_info();
		//TODO: find a better way to handle old web servers
		if($this->params['q']) $path = $this->params['q'];
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
			$this->process_route($pattern,$keys,$route);
		}
	}
}