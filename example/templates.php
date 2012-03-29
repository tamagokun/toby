<?php

require 'rackem/rackem.php';
require dirname(__FILE__).'/../router.php';

require 'Mustache.php';

$app = new \Router\Base();

$app->get("/", function($app) {
	return $app->mustache("some_template",array("layout_engine"=>"php"),array("name"=>"Mike"));
});

$app->run();