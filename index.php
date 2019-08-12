<?php

error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 'on');

/*
|--------------------------------------------------------------------------
| Statamic
|--------------------------------------------------------------------------
|
| Statamic is a flat-file, dynamic, and highly flexible publishing
| engine, built for developers, designers, and clients alike.
|
| @author Jack McDade (jack@statamic.com)
| @author Fred LeBlanc (fred@statamic.com)
| @copyright 2013 Statamic
|
*/


/*
|--------------------------------------------------------------------------
| Web Root
|--------------------------------------------------------------------------
|
| Lots of file level activities enjoy knowing right where web root is.
|
*/

define("BASE_PATH", str_replace('\\', '/',  __DIR__));

/*
|--------------------------------------------------------------------------
| Running Above Web Root
|--------------------------------------------------------------------------
|
| To run Statamic above webroot, uncomment the following line. Make sure
| to update the public folder setting in your site config.
|
*/

// chdir('..');


/*
|--------------------------------------------------------------------------
| Autoloader
|--------------------------------------------------------------------------
|
| "Autoload" the application dependencies and libraries
|
*/

require_once BASE_PATH . '/_app/autoload.php';

/*
|--------------------------------------------------------------------------
| Start the Engine
|--------------------------------------------------------------------------
|
| All the heavy initialization and configuration happens right here.
| Let's get going!
|
*/

$app = require_once BASE_PATH . '/_app/start.php';

$app->run();
