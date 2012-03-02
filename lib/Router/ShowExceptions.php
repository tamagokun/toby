<?php
namespace Router;

class ShowExceptions extends \Rackem\Exceptions
{
	public function call($env)
	{
		try
		{
			return $this->app->call($env);
		}catch(\Exception $e)
		{
			$this->handle_exception($env, $e);
			return array(500, array('Content-Type' => 'text/html'), array($this->error_template($env,$e)));
		}
	}

	private function pretty_array($array,$name="data")
	{
		$result = "";
		foreach($array as $key=>$value)
			$result .= "<tr><td>$key</td><td>$value</td></tr>";
		if(empty($result))
			return "<p>No $name.</p>";
		return "<table><tbody><tr><th>Variable</th><th>Value</th></tr>$result</tbody></table>";
	}

	private function pretty_trace($e)
	{
		$stack = array();
		preg_match_all('/#\d{1,}\s([^:]*):?(.*)/',$e->getTraceAsString(),$stack);
		$result = "";
		foreach($stack[1] as $index=>$file)
		{
			$block = !empty($stack[2][$index])? " in <code><strong>{$stack[2][$index]}</strong></code>" : "";
			$result .= "<li><code>$file</code>$block</li>";
		}
		return $result;
	}

	private function location($trace)
	{
		$block = isset($trace["function"])? "{$trace["function"]}" : "";
		if(isset($trace["class"]))
			$block = $trace["class"].$trace["type"].$block;
		return $block;
	}

	protected function error_template($env,$e)
	{
		$exception = get_class($e);
		$location = $this->location(array_shift($e->getTrace()));
		$path = ($this->app->request)? $this->app->request->path_info() : "/";
		$full_stack = $this->pretty_trace($e);
		$env_stack = $this->pretty_array($env);
		$get_stack = $this->pretty_array($env['rack.request.query_hash']);
		$post_stack = $this->pretty_array($env['rack.request.form_hash']);
		return <<<TEMPLATE
<!doctype html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>{$exception} at {$path}</title>
<style type="text/css" media="screen">
	* {margin: 0; padding: 0; border: 0; outline: 0;}
	body {background:#eeeeee; margin: 0; padding: 0;font-family: 'Lucida Grande','Lucida Sans Unicode','Garuda';
		color: #525252;}
	code {font-family: 'Lucida Console', monospace;}
	h2 {font-style:italic;}
	h2, h3 {padding: .5em 0;}
	ul {list-style: none;}
	#wrap {width:960px; margin: 20px auto; background:white; border:1px solid #ccc;border-radius:10px;padding:10px;}
	#summary li {display:inline; padding: 0 10px;}
	#backtrace ul {border:1px solid #e9e9e9; border-bottom: 0 none;}
	#backtrace li {font-size:0.858em; background: #f7f7f7;border-bottom:1px solid #e9e9e9;padding: 5px 0 5px 10px;}
	table {border: 1px solid #e9e9e9; border-spacing: 0; border-bottom: 0 none; border-left: 0 none;}
	table th, table td {font-size: 0.786em; padding: 3px 3px 3px 10px; 
		border: 1px solid #e9e9e9; border-top: 0 none; border-right: 0 none;}
	table th {background: #f7f7f7;}
</style>
</head>
<body>
	<div id="wrap">
		<div id="header">
			<h1><strong>{$exception}</strong> at <strong>{$path}</strong></h1>
			<h2>{$e->getMessage()}</h2>
			<div id="summary">
				<ul>
					<li><strong>file:</strong> <code>{$e->getFile()}</code></li>
					<li><strong>location:</strong> <code>in {$location}</code></li>
					<li><strong>line:</strong> <code>{$e->getLine()}</code></li>
				</ul>
			</div>
		</div>
		<div id="backtrace">
			<h3>BACKTRACE</h3>
			<ul>{$full_stack}</ul>
		</div>
		<div id="get">
			<h3>GET</h3>
			{$get_stack}
		</div>
		<div id="post">
			<h3>POST</h3>
			{$post_stack}
		</div>
		<div id="env">
			<h3>ENVIRONMENT</h3>
			{$env_stack}
		</div>
	</div>
</body>
</html>
TEMPLATE;
	}
}