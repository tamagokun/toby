<?php
namespace Toby;

class ShowExceptions extends \Rackem\ShowExceptions
{
	public function exception_handler($e, $rethrow = true)
	{
		ob_get_clean();
		if($rethrow) throw $e;
		return $this->error_template($e);
	}

	private function pretty_array($array,$name="data")
	{
		$result = "";
		foreach($array as $key=>$value)
		{
			if(!is_string($value)) $value = 'Object';
			$result .= "<tr><td>$key</td><td>$value</td></tr>";
		}
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

	protected function error_template($e,$env=null)
	{
		if(is_null($env)) $env = $this->env;
		$exception = get_class($e);
		$trace = $e->getTrace();
		$location = $this->location(array_shift($trace));
		$path = isset($this->app->request)? $this->app->request->path_info() : "/";
		$full_stack = $this->pretty_trace($e);
		$env_stack = $this->pretty_array($env);
		$get_stack = isset($env['rack.request.query_hash'])? $this->pretty_array($env['rack.request.query_hash']) : "";
		$post_stack = isset($env['rack.request.form_hash'])? $this->pretty_array($env['rack.request.form_hash']) : "";
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
	h3 {text-shadow: 0 1px #fff;}
	h2, h3 {padding: .5em 0;}
	ul {list-style: none;}
	#header {background:#F0B49E; padding: 10px 20px; border-top: 1px solid #f7d9ce;}
	#summary li {display:inline; padding: 0 10px 0 0;}
	#main {padding: 10px 20px; box-shadow: inset 0 1px 5px #bbb;}
	#main p {padding: 10px 0; font-size: 0.786em; text-shadow: 0 1px #fff;}
	#main ul {border:1px solid #e9e9e9; border-bottom: 0 none;}
	#main li {font-size:0.858em; background: #ffffff;border-bottom:1px solid #e9e9e9;padding: 5px 0 5px 10px;}
	table {border: 1px solid #e9e9e9; border-spacing: 0; border-bottom: 0 none; border-left: 0 none;}
	table th, table td {font-size: 0.786em; padding: 3px 3px 3px 10px; background: #ffffff;
		border: 1px solid #e9e9e9; border-top: 0 none; border-right: 0 none;}
	table th {background: #f7f7f7;}
</style>
</head>
<body>
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
	<div id="main">
		<h3>BACKTRACE</h3>
		<ul>{$full_stack}</ul>
		<h3>GET</h3>
		{$get_stack}
		<h3>POST</h3>
		{$post_stack}
		<h3>ENVIRONMENT</h3>
		{$env_stack}
		<p>You&#39;re seeing this error becuase you have enabled the <code>show_exceptions</code> setting.</p> 
	</div>
</body>
</html>
TEMPLATE;
	}
}
