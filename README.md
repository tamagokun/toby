# [Old Toby](http://www.youtube.com/watch?v=YAZpjWZRNAc)

_Making PHP development fun again._

[Sinatra](http://www.sinatrarb.com/) for PHP, built on Rack'em.

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

 - ensuring that chunked multi-part uploads work
 - support for more templating engines. (twig, jade, etc.)