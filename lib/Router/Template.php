<?php
namespace Router;

class Template
{	
	protected $file;
	
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
	
	public static function engine_extension($extension)
	{
		$extensions = array(
			"php" => array("php","html","html.php"),
			"markdown"	=> array("markdown","md"),
		);
		if(isset($extensions[$extension]))
			return $extensions[$extension];
		return null;
	}
}