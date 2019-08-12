<?php

// start measuring
define("STATAMIC_START", microtime(true));

global $is_debuggable_route;
$is_debuggable_route = false;

const STATAMIC_VERSION = '1.10.3';
const APP_PATH = __DIR__;

// handle the PHP development server
define("ENVIRONMENT_PATH_PREFIX", (php_sapi_name() === "cli-server") ? "index.php" : "");




// setting this now so that we can do things before the configurations are fully loaded
// without PHP freaking out in PHP 5.3.x
date_default_timezone_set('UTC');

/*
|--------------------------------------------------------------------------
| Autoload Slim
|--------------------------------------------------------------------------
|
| Bootstrap the Slim environment and get things moving.
|
*/

require_once __DIR__ . '/vendor/Slim/Slim.php';
require_once __DIR__ . '/vendor/SplClassLoader.php';

\Slim\Slim::registerAutoloader();

/*
|--------------------------------------------------------------------------
| Vendor libraries
|-------------------------------------------------------------------------
|
| Load miscellaneous third-party dependencies.
|
*/

$packages = array(
  'Buzz',
  'Carbon',
  'emberlabs',
  'Intervention',
  'Michelf',
  'Netcarver',
  'Stampie',
  'Symfony',
  'Whoops',
  'Zeuxisoo',
  'erusev',
  'Propel'
);

foreach ($packages as $package) {
  $loader = new SplClassLoader($package, __DIR__ . '/vendor/');
  $loader->register();
}

require_once __DIR__ . '/vendor/PHPMailer/PHPMailerAutoload.php';
require_once __DIR__ . '/vendor/Spyc/Spyc.php';
require_once __DIR__ . '/vendor/erusev/Parsedown.php';
require_once __DIR__ . '/vendor/erusev/ParsedownExtra.php';



/*
|--------------------------------------------------------------------------
| The Template Parser
|--------------------------------------------------------------------------
|
| Statamic uses a *highly* modified fork of the Lex parser, created by
| Dan Horrigan. Kudos Dan!
|
*/

require_once __DIR__ . '/vendor/Lex/Parser.php';


/*
|--------------------------------------------------------------------------
| Internal API & Class Autoloader
|--------------------------------------------------------------------------
|
| An autoloader for our internal API and other core classes
|
*/

// helper functions
require_once __DIR__ . '/core/exceptions.php';
require_once __DIR__ . '/core/functions.php';

// register the Statamic autoloader
spl_autoload_register("autoload_statamic");


// attempt HTML caching
// although this doesn't really have anything to do with autoloading, putting this
// here allows us to not force people to update their index.php files
if (Addon::getAPI('html_caching')->isEnabled() && Addon::getAPI('html_caching')->isPageCached()) {
    die(Addon::getAPI('html_caching')->getCachedPage());
}

// mark milestone for debug panel
Debug::markMilestone('autoloaders ready');
