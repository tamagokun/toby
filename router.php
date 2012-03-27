<?php
if(!class_exists("SplClassLoader")) require "vendor/SplClassLoader.php";

$loader = new SplClassLoader('Router', __DIR__.'/lib');
$loader->register();
