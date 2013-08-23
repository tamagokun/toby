<?php
namespace Toby\Template;

class Mustache extends \Toby\Template
{
	public function render($locals,$yield)
	{
		if(!class_exists("\\Mustache_Engine")) throw new \Rackem\Exception("Required class 'Mustache_Engine' not found.");
		$template = file_get_contents($this->file);
		$engine = new \Mustache_Engine();
		$locals["yield"] = $yield;
		ob_start();
		echo $engine->render($template,$locals);
		return ob_get_clean();
	}
}
