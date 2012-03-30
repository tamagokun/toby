<?php

require 'rackem/rackem.php';
require dirname(__FILE__).'/../toby.php';

require 'Mustache.php';

$app = new \Toby\Base();

$app->get("/", function($app) {
	return $app->mustache("some_template",array("layout_engine"=>"php"),array("name"=>"Mike"));
});

$app->run();