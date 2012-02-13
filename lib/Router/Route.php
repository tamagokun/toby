<?php
namespace Router;

class Route
{
	public $method,$path;
	private $block,$options;
	
	public function __construct($method,$path,$block,$options=array())
	{
		$this->method = $method;
		$this->path = $path;
		$this->block = $block;
		$this->options = $options;
	}
	
	public function __invoke()
	{
		$block = $this->block;
		return $block();
	}
	
	public function compile()
	{
		$keys = array();
		$pattern = preg_replace_callback("[^\?\%\\\/\:\*\w]", function($c) { return $c; }, $this->path);
		$pattern = preg_replace_callback("((:\w+)|\*)", function($match) use($keys) {
			if($match[0] == "*")
			{
				$keys[] = "splat";
				return "(.*?)";
			}
			$keys[] = $match[2];
			return "([^/?#]+)";
		}, $pattern);
		return array($pattern, $keys);
	}
}