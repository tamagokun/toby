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
		$this->request = new \Rackem\Request($env);
		$this->response = new \Rackem\Response();
		$this->params = $this->request->params();
		
		$this->reponse->finish();
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
	
	private function filter($where)
	{
		
	}
	
	private function route()
	{
			
	}
}