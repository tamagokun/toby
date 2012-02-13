<?php

require 'rackem/rackem.php';
require '../router.php';

$app = new \Router\Base();

$app->get('/hello/:name',function($name) use ($app) {
	$app->response->write("Hello, $name");
});

\Rackem\Rack::run($app);