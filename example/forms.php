<?php

//require 'rackem/rackem.php';
require '/Users/mkruk/Sites/php/rackem/rackem.php';
require dirname(__FILE__).'/../router.php';

$app = new \Router\Base();

$app->get("/",function($app) {
	return $app->php("index");
});

$app->get("/uploaded",function($app) {
	return $app->send_file("{$app->root}/uploads/bootstrap-popover.js");
});

$app->post("/",function($app) {
	//throw new \Exception();
	//file upload arrays:
		//name
		//type
		//tmp_name
		//error
		//size
	$handle = fopen("{$app->root}/uploads/{$app->params->image["name"]}","w");
	if($handle)
	{
		fwrite($handle,file_get_contents($app->params->image["tmp_name"]));
		fclose($handle);
	}
	return "Woo!";
});

$app->run();