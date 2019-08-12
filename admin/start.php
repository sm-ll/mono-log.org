<?php

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

date_default_timezone_set(Helper::pick($config['_timezone'], 'UTC'));

/*
|--------------------------------------------------------------------------
| Slim Initialization
|--------------------------------------------------------------------------
|
| Time to get an instance of Slim fired up. We're passing the $config
| array, which contains a bit more data than necessary, but helps keep
| everything simple.
|
*/

$config['whoops.editor'] = 'sublime';

$admin_app = new \Slim\Slim(array_merge($config, array('view' => new Statamic_View)));

// Initialize Whoops middleware
$admin_app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware);

$admin_app->config = $config;

$admin_app->config['_cookies.secret_key'] = Cookie::getSecretKey();

/*
|--------------------------------------------------------------------------
| Cookies for the Monster
|--------------------------------------------------------------------------
|
| Get the Slim Cookie middleware running the specified lifetime.
|
*/

session_cache_limiter(false);
session_start();


/*
|--------------------------------------------------------------------------
| Check for Disabled Control Panel
|--------------------------------------------------------------------------
|
| We need to make sure the control panel is enabled before moving any
| further. You know, security and stuff.
|
*/
$admin_enabled = Config::get('_admin_enabled', true);

if ($admin_enabled !== true && strtolower($admin_enabled) != 'yes') {
  Statamic_View::set_templates(array_reverse(array("denied")));
  Statamic_View::set_layout("layouts/disabled");
  $admin_app->render(null, array('route' => 'disabled', 'app' => $admin_app));
  exit();
}

/*
|--------------------------------------------------------------------------
| Set Default Layout
|--------------------------------------------------------------------------
|
| This may be overwritten later, but let's go ahead and set the default
| layout file to start assembling our front-end view.
|
*/

Statamic_View::set_cp_view();
Statamic_View::set_layout("layouts/default");

/*
|--------------------------------------------------------------------------
| Set Global Variables, Defaults, and Environments
|--------------------------------------------------------------------------
|
| Numerous tag variables, helpers, and other config-dependent options
| need to be loaded *before* the page is parsed.
|
*/

Statamic::setDefaultTags();


/*
|--------------------------------------------------------------------------
| Caching
|--------------------------------------------------------------------------
|
| Look for updated content to cache
|
*/
_Cache::update();


/*
|--------------------------------------------------------------------------
| Load Admin Libraries
|--------------------------------------------------------------------------
|
| Admin has a few extra needs. Let's fetch those.
|
*/

require __DIR__ . '/helper.php';

/*
|--------------------------------------------------------------------------
| The Routes
|--------------------------------------------------------------------------
|
| Route it up fellas!
|
*/
require __DIR__ . '/routes.php';

return $admin_app;
