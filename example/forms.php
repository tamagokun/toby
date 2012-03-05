<?php

require 'rackem/rackem.php';
require dirname(__FILE__).'/../router.php';

$app = new \Router\Base();

$app->get("/",function($app) {
	return $app->php("index");
});

$app->post("/",function($app) {

});

$app->run();