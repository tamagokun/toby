<?php

require 'rackem/rackem.php';
require '../router.php';

$app = new \Router\Base();

$app->configure(function($app) {
	$app->set("public_folder","{$app->root}/lots_of_pooop");
});

$app->get('/hello/:name/:place',function($app) {
	//return $app->pass();
	$app->response->write($app->public_folder);
	return $app->php("testing",array(),array("name"=>$app->params->name,"place"=>$app->params->place));
});

class Testing
{
	public static function hello($app)
	{
		return $app->halt(404);
		return "Something got passed, so I matched! YAY!";
	}
}

$app->get('/hello/*',"Testing::hello");

\Rackem\Rack::run($app);