<h1>Some kind of form</h1>
<form method="POST" action="<?php echo $app->url('/'); ?>" enctype="multipart/form-data">
	<p>
		<input type="text" name="name" value="" />
	</p>
	<p>
		<input type="file" name="image" />
	</p>
	<p>
		<input type="submit" value="ok" />
	</p>
</form>