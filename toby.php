<?php
if(!class_exists("SplClassLoader")) require "vendor/SplClassLoader.php";

$loader = new SplClassLoader('Toby', __DIR__.'/lib');
$loader->register();