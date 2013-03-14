<h1>Old Toby</h1>

_Making PHP development fun again._

![](img/old_toby.png)

The finest Leaf in the Southfarthing is also a web framework similar to Sinatra.

Built on [Rack'em](https://github.com/tamagokun/rackem).

## Getting Started

Your typical example using Toby:

```php
<?php
# config.php
require 'vendor/autoload.php';

$app = new \Toby\Base();
$app->get('/', function($app) {
    return "Hello, World!";
});

return $app->run();
```

```shell
$ vendor/bin/rackem # serve app using rackem built-in web server
```

### Installation

Use [Composer](http://getcomposer.org/doc/00-intro.md):

```json
{
  "require": {
      "toby/toby": "dev-master"
    },
    "minimum-stability": "dev"
}
```

```bash
$ composer install
```

_There is an autoloader at `toby.php` if you don't like Composer_

## Routing

Routes define how HTTP requests are handled by Toby. A route consists of an HTTP method and pattern to match, which are associated with a callable function.

```php
<?php

$app->get('/', function($app) {
  // matches GET to "/"
});

$app->post('/', function($app) {
   // matches POST to "/"
});

$app->put('/', function($app) {
   // matches PUT to "/"
});

$app->patch('/', function($app) {
   // matches PATCH to "/"
});

$app->delete('/', function($app) {
  // matches DELETE to "/"
});

$app->options('/', function($app) {
  // matches OPTIONS to "/"
});

$app->any('/', function($app) {
  // matches any HTTP method to "/"
});
```

Anything that is __return__ed from a route will be used as the response body. If you use things like `echo` those will be passed into the error log or the `X-Output` header.

### Matching

Routes are processed in the order they are defined in your app. The first route that matches the request will be invoked.

You can create named parameters in your match pattern, which are accessible in your block:

```php
$app->get('/eat/:food', function($app, $food) {
    // this will match /eat/cheese
    echo $food; # cheese
    echo $app->params->food; #cheese
});
```
You can use wildcards in your patterns, which are all accesible via a splat parameter:

```php
$app->get('/file/*.*', function($app) {
    // GET /file/test.pdf
    return $app->params->splat; # array("test","pdf");
});
```

Match optional parameters:

```php
$app->get('/api/feed.?:format?', function($app) {
    //matches /api/feed or /api/feed.xml or /api/feed.json
});
```

Match using regular expressions:

```php
$app->get('');
```

### Conditions

You can further define how a request is matched by using route conditions. Conditions are a callable function that must return true for the route to match and be invoked. If the condition returns false, Toby will continue on to the next route looking for a match.

```php
$app->condition('auth', function($role) {
    return User::current()->is($role);
});

$app->post('/update/settings', array('auth'=>"admin"), function($app) {
    Settings::update($app->params->settings);
    $app->redirect('/update');
});
```

## Static Files

You can serve static files by default from `./public`. You can also modify this location:

```php
$app->public_folder = __DIR__."/assets";
```

Remember that served files don't use their public folder location in the url:
`http://localhost/css/style.css` would serve `public/css/style.css`

## Templates

Toby expects any route to return a string value to be rendered. You can use a template language with their respective helper method:

```php
$app->get("/", function($app) {
    # renders views/index.html.php
    # or      views/index.php
    # or      views/index.html
    return $app->php("index");
});
```

You can also pass an array as the second parameter to specify options:

```php
$app->get("/", function($app) {
    return $app->haml("article", array("layout"=>"blog"));
});
```

This will render `views/article.haml` inside of the layout template `views/blog.html` (default layout name is `layout`).

By default Toby will use `./views` to look for templates, but you can specify a different location with the `views` setting:

```php
$app->set("views", __DIR__."/templates");
```

Options
....

Available Template Languages
...

## Filtering

To run code before or after a route is processed, you can use filters:

```php
$app->before(function($app) {

});

$app->after(function($app) {

});
```

Filters are essentially special routes, so you can also use path matching and conditions with filters:

```php
$app->before('/admin*', array('auth'=>'admin'), function($app) {
    $app->welcome_text = "Welcome, {$app->user->name}!";
});
```

This will only be called if the GET request begins with `/admin` and the `auth` condition returns true.

## Redirection

At any point in a code block, you can redirect to a different location:

```php
$app->get("/", function($app) {
    return $app->redirect("/dashboard");
});
```

## Halt and Pass

To immediately stop processing a route, use halt:

```php
$app->halt();
$app->halt(500);
$app->halt("I can't let you do that, Dude");
```

If you would rather just leave the current route, and let Toby go on to process the next matching route, use pass:

```php
$app->get("/", function($app) {
    return $app->pass();
});

$app->get("/*.css", function($app) {

});
```

## Errors

You can set routes to take care of any error handling during your application.


To catch any thrown `Exception`:
```php
$app->error(function($app) {
    return "<h1>Something happened!</h1>";
});
```

You can also catch HTTP error codes:

```php
# if response status code is 404.
$app->not_found(function($app) {
    return "<h1>Sorry, idk where this is.</h1>";
});

# if response status code is 500.
$app->error(500, function($app) {
    return "<h1>GAH!</h1>";
});
```

## Logging

Application logging happens on the Rack'em level, but Toby makes it a bit easier:

```php
$app->get("/", function($app) {
    $app->logger()->info("Request to /");
});
```

## Configure

You can configure application settings as soon as you instantiate an instance of it, but you can also run a configuration block before route processing:

```php
$app->configure(function($app) {
    $app->db = DB::connect('localhost');
});
```

You can even have configuration block for particular environments:

```php
$app->configure("production", function($app) {
    $app->show_exceptions = false;
});
```

You can access settings directly on the application instance, or with the settings variable. You can also use `set` or `enable` or `disable` for better readability.

```php
$app->configure(function($app) {
    $app->set("views", __DIR__."/assets/views");
    $app->enable("protection");
    $app->disable("show_exceptions");
});

$app->get("/", function($app) {
    echo $app->views; # /assets/views
    echo $app->settings->protection; # true
    echo $app->show_exceptions; # false
});
```

## Uploads

## Flash Messages





