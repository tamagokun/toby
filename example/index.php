<?php

require 'rackem/rackem.php';
require dirname(__FILE__).'/../router.php';

$app = new \Router\Base();

$app->configure(function($app) {
	$app->set("public_folder","{$app->root}/lots_of_pooop");
});

$app->before(function($app) {
	throw new \Exception('boom');
	return "";
});

$app->before("/hello/*/*/*",function($app) {
	$app->response->write($app->params->splat);
	return "I only run on hello!";
});

$app->condition("testing",function() { return true; });

$app->get('/hello/:name/:place',function($app) {
	//return $app->pass();
	return $app->redirect("/hello/");
	$app->response->write($app->public_folder);
	return $app->php("testing",array(),array("name"=>$app->params->name,"place"=>$app->params->place));
});

$app->get('/',function($app) {
	return random_function();
});

class Testing
{
	public static function hello($app)
	{
		//return $app->halt(404);
		throw new MyException('boom');
		return "Something got passed, so I matched! YAY!";
	}
}

function random_function()
{
	return "DUDE, YEAH";
}

class MyException extends Exception {}

$app->get('/hello/*',array("testing"=>"sojfosdjfsdF"),"Testing::hello");

$app->not_found(function($app) {
	return "Doh!! I was not found :(";
});

$app->error('MyException',function($app) {
	return "GAH!!!!!!!!!!!!!!!!!";
});

//\Rackem\Rack::use_middleware("\Router\ShowExceptions");
//\Rackem\Rack::run($app);
$app->run();