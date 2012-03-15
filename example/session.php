<?php

require 'rackem/rackem.php';
require dirname(__FILE__).'/../router.php';

$app = new \Rackem\Base();

$app->enable("sessions");

$app->get("/", function($app) {
	$app->session("value") = "Hello World!";
	return "The cookie you created contains the value: {$app->session('value')}";
});