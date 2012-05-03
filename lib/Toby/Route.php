<?php
namespace Toby;

class Route
{
	public $method,$path,$conditions,$name;
	private $block;
	
	public function __construct($method,$path,$block,$conditions=array())
	{
		$this->method = is_array($method) || is_null($method)? $method : array($method);
		$this->path = $path;
		$this->block = $block;
		$this->conditions = array();
		foreach($conditions as $condition) $this->conditions = array_merge($this->conditions,$condition);
	}
	
	public function __invoke($app,$params)
	{
		return call_user_func_array($this->block,array_merge(array($app),$params));
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
	
	public function name($value)
	{
		$this->name = $value;
		return $this;
	}
	
	public function via()
	{
		$this->method = func_get_args();
		return $this;
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