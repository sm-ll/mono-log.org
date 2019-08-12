<?php

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
| @copyright 2012 Statamic
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

// define site root
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
define("SITE_ROOT", str_replace($doc_root, '', BASE_PATH) . '/');
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

require __DIR__ . '/_app/autoload.php';

/*
|--------------------------------------------------------------------------
| Load Configs
|--------------------------------------------------------------------------
|
| We need to load the configs here because we don't necessarily know
| the name of the admin folder.
|
*/

$config = Statamic::loadAllConfigs(true);

/*
|--------------------------------------------------------------------------
| Application Timezone
|--------------------------------------------------------------------------
|
| Many users are upgrading to PHP 5.3 for the first time. I know.
| We've gone ahead set the default timezone that will be used by the PHP
| date and date-time functions. This prevents some potentially
| frustrating errors for novice developers.
|
*/
date_default_timezone_set(Helper::pick($config['_timezone'], @date_default_timezone_get(), "UTC"));

/*
|--------------------------------------------------------------------------
| Start the Engine
|--------------------------------------------------------------------------
|
| All the heavy initilization and configuration happens right here.
| Let's get going!
|
*/

$admin_app = require_once __DIR__ . '/' . $config['_admin_path'] . '/start.php';

$admin_app->run();
