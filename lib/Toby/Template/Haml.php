<?php
namespace Toby\Template;

class Haml extends \Toby\Template
{
	public function render($locals,$yield)
	{
		if(!class_exists("\\HamlPHP")) throw new \Exception("Required class 'HamlPHP' not found.");
		$cache_dir = $this->find_cache_dir(__DIR__);
		if(!$cache_dir) throw new \Exception("Could not find a .cache directory for haml engine.");
		$engine = new \HamlPHP(new \FileStorage($cache_dir));
		$locals["yield"] = $yield;
		ob_start();
		echo $engine->parseFile($this->file,$locals);
		return ob_get_clean();
	}
	
	public function find_cache_dir($dir)
	{
		$dir = rtrim($dir,'/').'/';
		if(!is_dir($dir)) return false;
		if(file_exists("$dir.cache")) return "$dir.cache/";
		if($dir == '/') return false;
		return $this->find_cache_dir(dirname($dir));
	}
}