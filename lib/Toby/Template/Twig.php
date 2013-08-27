<?php
namespace Toby\Template;

class Twig extends \Toby\Template
{
	public function render($locals,$yield)
	{
		if(!class_exists("\\Twig_Environment")) throw new \Rackem\Exception("Required class 'Twig_Environment' not found.");
		$template = file_get_contents($this->file);
		$engine = new \Twig_Environment(new Twig_Loader_String());
		$locals["yield"] = $yield;
		ob_start();
		echo $engine->render($template,$locals);
		return ob_get_clean();
	}
}
