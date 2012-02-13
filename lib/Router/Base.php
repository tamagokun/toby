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
			$this->filter("before");
			$this->route();
		}catch(Exception $e)
		{
			
		}
		$this->filter("after");
	}
	
	private function filters($where)
	{
		
	}
	
	private function route($method,$path,$block,$options=array())
	{
		
	}
	
	private function routes()
	{
			
	}
}