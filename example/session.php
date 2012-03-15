<?php

require 'rackem/rackem.php';
require dirname(__FILE__).'/../router.php';

$app = new \Router\Base();

$app->enable("sessions");

$app->get("/", function($app) {
	$app->env["rack.session"]["value"] = "Hello World!";
	$app->response->set_cookie()
	return "The cookie you created contains the value: {$app->request->session('value')}";
});

$app->run();