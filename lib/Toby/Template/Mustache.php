<?php
namespace Toby\Template;

class Mustache extends \Toby\Template
{
	public function render($locals,$yield)
	{
		if(!class_exists("\\Mustache")) throw new \Rackem\Exception("Required class 'Mustache' not found.");
		$template = file_get_contents($this->file);
		$engine = new \Mustache();
		$locals["yield"] = $yield;
		ob_start();
		echo $engine->render($template,$locals);
		return ob_get_clean();
	}
}