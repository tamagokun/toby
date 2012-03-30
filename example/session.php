<?php

require 'rackem/rackem.php';
require dirname(__FILE__).'/../toby.php';

$app = new \Toby\Base();

$app->enable("sessions");
$app->sessions = array("key"=>"router_session","domain"=>"dev.local","expire_after"=>3600);

$app->get("/", function($app) {
	$app->env["rack.session"]["value"] = "Hello World!";
	$app->flash("error","There was an error!");
	return $app->php("index");
});

$app->post("/", function($app) {
	//return array("<pre>",print_r($app->env,true));
	return "<h1>".$app->flash("error")."</h1>";
});

$app->get("/hello", function($app) {
	return print_r($app->request->session(),true);
	//return "Checking for... {$app->request->cookies('some_stuff')}";
});

$app->run();