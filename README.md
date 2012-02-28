# Possibly the best PHP web application framework

## What we have so far

 - routing (`DELETE`,`GET`,`HEAD`,`OPTIONS`,`PATCH`,`POST`,`PUT`)
 - route matching (`$app->get("/hello/:name");`)
 - render templates and layouts
 	 - currently supported: `php`
 - conditions and filters
 - redirection
 - halting
 - passing routes
 - error handling (`error` `not_found` `halt(500)`)
 - settings
 - configure blocks (global and per environment)
 - completely Rack complient (using Rack'em)
 
## What needs doing

 - helpers for loggers
 - serving static files and multipart (this may be something for Rack'em)
 - handling sessions (again, might be something for Rack'em)
 - templating engines (haml,twig,mustache,markdown) at the very least
 - a proper name (Router? What was I thinking?)