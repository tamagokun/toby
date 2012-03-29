<?php

require 'rackem/rackem.php';
require dirname(__FILE__).'/../router.php';

$app = new \Router\Base();

$app->enable("sessions");
$app->sessions = array("domain"=>"dev.local","expire_after"=>3600);

$app->get("/", function($app) {
	$app->env["rack.session"]["value"] = "Hello World!";
	$app->flash("error","There was an error!");
	//$app->response->set_cookie("some_stuff",array("value"=>"Hello World!"));
	//return "The cookie you created contains the value: {$app->request->session('value')}";
	return $app->php("index");
});

$app->post("/", function($app) {
	return $app->php("post");
});

$app->get("/hello", function($app) {
	return print_r($app->request->session(),true);
	//return "Checking for... {$app->request->cookies('some_stuff')}";
});

$app->run();