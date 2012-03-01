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
			$path = ($this->app->request)? $this->app->request->path_info() : "/";
			$full_backtrace = $this->pretty_trace();
			return array(500, array('Content-Type' => 'text/html'), $error_template);
		}
	}

	private function pretty_trace()
	{
		return "";
	}
}

$error_template = <<<'TEMPLATE'
<!doctype html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>{get_class($e)} at {$path}</title>

</head>
<body>
	<div id="wrap">
		<div id="masthead">
			<h1><strong>{get_class($e)}</strong> at <strong>{$path}</strong></h1>
			<h2>{$e->getMessage()}</h2>
			<div id="summary">
				<ul>
					<li><strong>file:</strong> <code>{$e->getFile()}</code></li>
					<li><strong>location:</strong> <code>{$e->getTraceAsString()}</code></li>
					<li><strong>line:</strong> <code>{$e->getLine()}</code></li>
				</ul>
			</div>
		</div>
		<div id="backtrace">
			<h3>BACKTRACE</h3>
			<ul>
				{$full_backtrace}
			</ul>
		</div>
	</div>
</body>
</html>
TEMPLATE;
?>