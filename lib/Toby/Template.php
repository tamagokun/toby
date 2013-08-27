<?php
namespace Toby;

class Template
{	
	protected $file;
	public static $engines = array(
		"coffee"   => array("engine"=>"","ext"=>array("coffee")),
		"haml"     => array("engine"=>"\\Toby\\Template\\Haml","ext"=>array("haml")),
		"jade"     => array("engine"=>"","ext"=>array("jade")),
		"less"     => array("engine"=>"","ext"=>array("less")),
		"php"      => array("engine"=>null,"ext"=>array("php","html","html.php")),
		"markdown" => array("engine"=>"","ext"=>array("markdown","md","mkd")),
		"mustache" => array("engine"=>"\\Toby\\Template\\Mustache","ext"=>array("mustache")),
		"sass"     => array("engine"=>"","ext"=>array("sass")),
		"scss"     => array("engine"=>"","ext"=>array("scss")),
		"twig"     => array("engine"=>"\\Toby\\Template\\Twig","ext"=>array("twig"))
	);
	
	public function __construct($file)
	{
		$this->file = $file;
	}
	
	public function render($locals,$yield)
	{
		extract($locals);
		ob_start();
		require $this->file;
		return ob_get_clean();
	}
	
	public static function engine($key)
	{
		return isset(self::$engines[$key])? self::$engines[$key]["engine"] : false;
	}
	
	public static function engine_extension($extension)
	{
		if(isset(self::$engines[$extension]))
			return self::$engines[$extension]["ext"];
		return null;
	}
	
	public static function register($key,$class,$extentions=array())
	{
		if(!isset(self::$engines[$key])) self::$engines[$key] = array("engine"=>null,"ext"=>$extensions);
		self::$engines[$key]["engine"] = $class;
	}
}
