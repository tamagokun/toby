<?php
namespace Router;

class Base
{
	protected $app;
	protected $conditions;
	protected $routes;
	protected $filters;
	protected $errors;
	
	public function __construct($app=null)
	{
		$this->app = $app;
	}
	
	public function call($env)
	{
		$this->env = $env;
		//$this->request = new \Rackem\Request($env);
		$this->response = new \Rackem\Response();
		//if($this->app)
		//	$this->response->append_app($this->app->call());
		//$this->params = $this->request->params();
		$this->dispatch();
		$this->reponse->finish();
	}
	
	public function get($path)
	{
		$options = array_slice(func_get_args(),1);
		$block = array_pop($block);
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
		$path = $this->request->path();
		$match = preg_match_all($pattern,$path);
		if(count($match) < 2) return false;
		$route();
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