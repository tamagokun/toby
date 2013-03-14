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
	},
	"minimum-stability": "dev"
}
```

```sh
$ composer install
```

## Hello World

```php
<?php
# config.php

require 'vendor/autoload.php';

$app = new \Toby\Base();
$app->get('/',function() {
    return "<h1>Hello World!</h1>";
});
$app->run();
?>
```

```sh
$ vendor/bin/rackem
$ open http://localhost:9393
```

## [Check out the manual](http://ripeworks.com/toby)

## What we have so far

 - routing (`DELETE`,`GET`,`HEAD`,`OPTIONS`,`PATCH`,`POST`,`PUT`)
 - route matching (`$app->get("/hello/:name");`)
 - render templates and layouts
 	 - currently supported: `php`,`mustache`,`markdown`,`haml`
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
