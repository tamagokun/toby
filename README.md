# Possibly the best PHP web application framework

## What we have so far

 - routing (`DELETE`,`GET`,`HEAD`,`OPTIONS`,`PATCH`,`POST`,`PUT`)
 - route matching (`$app->get("/hello/:name");`)
 - render templates and layouts
 	 - currently supported: `php`
 - redirection
 - halting
 - passing routes
 - settings
 - configure blocks (global and per environment)
 - completely Rack complient (using Rack'em)
 
## What needs doing

 - conditions and filters
 - helpers for errors and loggers
 - serving static files and multipart (this may be something for Rack'em)
 - handling sessions (again, might be something for Rack'em)
 - templating engines (haml,twig,mustache,markdown) at the very least
 - a proper name (Router? What was I thinking?)