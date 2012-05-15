# [Old Toby](http://www.youtube.com/watch?v=YAZpjWZRNAc)

_Making PHP development fun again._

The finest Leaf in the Southfarthing is also a web framework similar to [Sinatra](http://www.sinatrarb.com/).

Built on [Rack'em](https://github.com/tamagokun/rackem).

## Setting Up

Toby works best with [Composer](http://getcomposer.org/). Add it to your `composer.json`

```json
{
  "require": {
	  "toby/toby": "dev-master"
	}
}
```
Install `composer install`
Autoload `require 'vendor/autoload.php';`

You can also load via PSR-0 by using `require 'toby.php';`

## Hello World

```php
<?php

$app = new \Toby\Base();
$app->get('/',function() {
    return "Hello World!";
});
$app->run();
?>
```

## What we have so far

 - routing (`DELETE`,`GET`,`HEAD`,`OPTIONS`,`PATCH`,`POST`,`PUT`)
 - route matching (`$app->get("/hello/:name");`)
 - render templates and layouts
 	 - currently supported: `php`,`mustache`,`markdown`,`haml`
 	 - more to come!
 - conditions and filters
 - redirection
 - halting
 - passing routes
 - error handling (`error` `not_found` `halt(500)`)
 - logging via Rackem\Logger
 - settings
 - configure blocks (global and per environment)
 - completely Rack complient (using Rack'em)
 - serving static files
 - handling file uploads
 - handling sessions (`$app->enable("sessions");`)
 - flash messaging (`$app->flash("error","Gah! Something happened!");`)
 
## What needs doing

 - support for more templating engines. (twig, jade, etc.)
