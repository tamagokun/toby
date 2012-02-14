<?php

require 'rackem/rackem.php';
require '../router.php';

$app = new \Router\Base();

$app->get('/hello/:name',function($name) use ($app) {
	return $app->php("testing",array(),array("name"=>$name));
});

\Rackem\Rack::run($app);