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
			return array(500, array('Content-Type' => 'text/html'), array($this->error_template($e)));
		}
	}

	private function pretty_trace()
	{
		return "";
	}

	protected function error_template($e)
	{
		$exception = get_class($e);
		$path = ($this->app->request)? $this->app->request->path_info() : "/";
		$full_backtrace = $this->pretty_trace();
		return <<<TEMPLATE
<!doctype html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>{$exception} at {$path}</title>

</head>
<body>
	<div id="wrap">
		<div id="masthead">
			<h1><strong>{$exception}</strong> at <strong>{$path}</strong></h1>
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
	}
}