<?php
namespace Router;

class Route
{
	public $method,$path,$conditions;
	private $block;
	
	public function __construct($method,$path,$block,$conditions=array())
	{
		$this->method = $method;
		$this->path = $path;
		$this->block = $block;
		$this->conditions = array();
		foreach($conditions as $condition) $this->conditions = array_merge($this->conditions,$condition);
	}
	
	public function __invoke()
	{
		return call_user_func_array($this->block,func_get_args());
	}
	
	public function compile()
	{
		$keys = array();
		$pattern = preg_replace_callback('/[^\?\%\\/\:\*\w]/', function($c) { return Route::encoded($c); }, $this->path);
		$pattern = preg_replace_callback('/(:(\w+)|\*)/', function($match) use(&$keys) {
			if($match[0] == "*")
			{
				$keys[] = "splat";
				return "(.*?)";
			}
			$keys[] = $match[2];
			return "([^/?#]+)";
		}, $pattern);
		return array("~^$pattern$~", $keys);
	}
	
	public static function encoded($char)
	{
		$char = $char[0];
		$enc = urlencode($char);
		if($enc == $char)
			$enc = "(?:".preg_quote($enc)."|".urlencode($char).")";
		if($char == " ")
			$enc = "(?:$enc|".Route::encoded("+").")";
		return $enc;
	}
}