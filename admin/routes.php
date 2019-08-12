<?php

/**
 * The Routes
 **/

function authenticateForRole($role = 'member')
{
  $admin_app = \Slim\Slim::getInstance();
  $user    = Auth::getCurrentMember();
  if ($user) {
    if ($user->hasRole($role) === false) {
      $admin_app->redirect($admin_app->urlFor('denied'));
    }
  } else {
    $admin_app->redirect($admin_app->urlFor('login'));
  }

  return true;
}

function isCurlEnabled()
{
  return function_exists('curl_version') ? true : false;
}

function doStatamicVersionCheck($app)
{
  if ( ! extension_loaded('mcrypt')) {
	  throw new FatalException('You must have the mcrypt PHP extension installed.');
  }

  // default values
  $app->config['latest_version_url'] = '';
  $app->config['latest_version']   = '';

  if (isCurlEnabled()) {
    $cookie = $app->getEncryptedCookie('stat_latest_version');
    if (!$cookie) {
      $license  = Config::getLicenseKey();
      $site_url = Config::getSiteURL();
      $parts  = parse_url($site_url);
      $domain   = isset($parts['host']) ? $parts['host'] : '/';

      $url = "http://outpost.statamic.com/check?v=" . urlencode(STATAMIC_VERSION) . "&l=" . urlencode($license) . "&d=" . urlencode($domain);
      $ch  = curl_init($url);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, '3');
      $content = trim(curl_exec($ch));
      curl_close($ch);

      if ($content <> '') {
        $response = json_decode($content);
        if ($response && $response->status == 'ok') {
          $app->setEncryptedCookie('stat_latest_version', $response->current_version);
          $app->setEncryptedCookie('stat_latest_version_url', $response->url);
          $app->config['latest_version_url'] = $response->current_version;
          $app->config['latest_version']   = $response->current_version;
        } else {
          $app->config['latest_version_url'] = '';
          $app->config['latest_version']   = '';
        }
      }
    } else {
      $app->config['latest_version']   = $cookie;
      $app->config['latest_version_url'] = $app->getEncryptedCookie('stat_latest_version_url');
    }
  }
}

/////////////////////////////////////////////////////////////////////////////////////////////////
// ROUTES
/////////////////////////////////////////////////////////////////////////////////////////////////

$admin_app->get('/', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  if (!CP_Helper::show_page('dashboard')) {
    $admin_app->redirect($admin_app->urlFor('pages'));
  }

  $template_list = array("dashboard");
  Statamic_View::set_templates(array_reverse($template_list));
  $admin_app->render(null, array('route' => 'dashboard', 'app' => $admin_app));
})->name('dashboard');


// AUTH RELATED FUNCTION
// --------------------------------------------------------
$admin_app->get('/denied', function () use ($admin_app) {
  $template_list = array("denied");
  Statamic_View::set_templates(array_reverse($template_list));
  Statamic_View::set_layout("layouts/login");
  $admin_app->render(null, array('route' => 'login', 'app' => $admin_app));
})->name('denied');


$admin_app->get('/login', function () use ($admin_app) {
  $template_list = array("login");
  Statamic_View::set_templates(array_reverse($template_list));
  Statamic_View::set_layout("layouts/login");
  $admin_app->render(null, array('route' => 'login', 'app' => $admin_app));
})->name('login');


$admin_app->post('/login', function () use ($admin_app) {
  $app = \Slim\Slim::getInstance();

  $login  = Request::post('login');
  $username = $login['username'];
  $password = $login['password'];

  $errors = array();

  // Auth login
  // if success direct to admin homepage
  if (Auth::login($username, $password)) {

    $user = Auth::getMember($username);

    if (!$user->hasHashedPassword()) {
      $user->set('password', $password, true);
      $user->save();
      Auth::login($username, $password);
    }

    $redirect_to = Config::get('_admin_start_page', 'pages');
    $app->redirect($app->urlFor($redirect_to));

  } else {
    $errors = array('error' => Localization::fetch('incorrect_username_password'));
  }

  $template_list = array("login");
  Statamic_View::set_templates(array_reverse($template_list));
  Statamic_View::set_layout("layouts/login");
  $admin_app->render(null, array('route' => 'login', 'app' => $admin_app, 'errors' => $errors));

})->name('login-submit');


$admin_app->get('/logout', function () use ($admin_app) {
  Auth::logout();
  $admin_app->redirect($admin_app->urlFor('dashboard'));
})->name('logout');


// ERROR FUNCTION
// --------------------------------------------------------
$admin_app->get('/error', function () use ($admin_app) {
  $template_list = array("error");
  Statamic_View::set_templates(array_reverse($template_list));
  Statamic_View::set_layout("layouts/default");
  $admin_app->render(null, array('route' => 'login', 'app' => $admin_app));
})->name('error');


// PUBLICATION
// --------------------------------------------------------
$admin_app->get('/pages', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);
  $template_list = array("pages");

  /*
    |--------------------------------------------------------------------------
    | Check if file is writable
    |--------------------------------------------------------------------------
    |
    | We now have a file name. Let's check if we can write to this thing.
    |
    */

  if (!Statamic::is_content_writable()) {
    $url = $admin_app->urlFor('error') . "?code=content_not_writable";
    $admin_app->redirect($url);
  }

  $path   = "";
  $path   = $admin_app->request()->get('path');
  $errors = array();


  /*
    |--------------------------------------------------------------------------
    | Pages and Home page
    |--------------------------------------------------------------------------
    |
    | We can get all the pages from get_content_tree(), but the home page
    | is a bit of an exception. We need to set a few things manually.
    |
    */

  $pages = Statamic::get_content_tree('/', 1, 1000, false, false, false, true, '/');

  // Home page isn't included by default
  $meta = Statamic::get_content_meta("page", '');

  $home_page = array(
    'type'          => 'home',
    'url'           => "/page",
    'slug'          => "/",
    'title'         => array_get($meta, 'title', Localization::fetch('home')),
    'has_entries'   => (File::exists(Path::tidy(Config::getContentRoot()."/fields.yaml"))) ? true : false,
    'entries_label' => array_get($meta, 'entries_label', Localization::fetch('entries')),
    'depth'         => 1
  );

  // Merge into pages
  array_unshift($pages, $home_page);

  /*
    |--------------------------------------------------------------------------
    | Fieldsets
    |--------------------------------------------------------------------------
    |
    | Get all the available fieldsets, removing any hidden ones as necessary
    |
    */

  $fieldsets = Statamic_Fieldset::get_list();

  foreach ($fieldsets as $key => $fieldset) {

    // Remove hidden fieldsets
    if (isset($fieldset['hide']) && $fieldset['hide'] === true) {
      unset($fieldsets[$key]);

      // set a fallback name
    } elseif (!isset($fieldset['title'])) {
      $fieldsets[$key]['title'] = Slug::prettify($key);
    }

  }

  // Sort fieldsets by title
  uasort($fieldsets, function ($a, $b) {
    return strcmp($a['title'], $b['title']);
  });

  #######################################################################

  Statamic_View::set_templates(array_reverse($template_list));
  $admin_app->render(null, array(
      'route'     => 'pages',
      'app'       => $admin_app,
      'errors'    => $errors,
      'path'      => $path,
      'pages'     => $pages,
      'fieldsets'   => $fieldsets,
      'are_fieldsets' => count($fieldsets) > 0 ? true : false,
      'listings'    => Statamic::get_listings()
    )
  );
})->name('pages');


$admin_app->get('/entries', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);
  $template_list = array("entries");

  $path   = $admin_app->request()->get('path');
  $errors = array();

  if ($path) {
    $entry_type = Statamic::get_entry_type($path);

    $order = $entry_type == 'date' ? 'desc' : 'asc';

    $entries = Statamic::get_content_list($path, null, 0, true, true, $entry_type, $order, null, null, true);
    Statamic_View::set_templates(array_reverse($template_list));

    $admin_app->render(null, array(
        'route'  => 'entries',
        'app'    => $admin_app,
        'errors'   => $errors,
        'path'   => $path,
        'folder'   => Path::addStartingSlash(preg_replace(Pattern::NUMERIC, '', $path)),
        'entries'  => $entries,
        'type'   => $entry_type,
        'listings' => Statamic::get_listings()
      )
    );
  }

})->name('entries');

// LOGIC
// - VALIDATE
// - SAVE TO ORIGINAL FILENAME
// - IF NECESSARY: RENAME

// POST: PUBLISH
$admin_app->post('/publish', function () use ($admin_app) {

  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $content_root = Config::getContentRoot();
  $content_type = Config::getContentType();

  $app  = \Slim\Slim::getInstance();
  $path = Request::get('path');

  if ($path) {
    $index_file = false;
    $form_data  = Request::post('page');

    // By HTML specifications, browsers are required to canonicalize line breaks
    // in user input to CR LF (\r\n), and I don’t think any browser gets this wrong.
    // Reference: clause 17.13.4 Form content types in the HTML 4.01 spec.
    // Source: http://stackoverflow.com/questions/14217101/what-character-represents-a-new-line-in-a-text-area
    // Anyway, we want \n for this
    array_walk_recursive($form_data, function (&$item, $key) {
      $item = str_replace("\r\n", Config::get('admin_line_endings', "\n"), $item);
    });

    // 1. Validate
    if ($form_data) {
      // ### Intercept the timestamp and convert to something we can work with
      if (isset($form_data['meta']['publish-time'])) {
        $_ts                 = $form_data['meta']['publish-time'];
        $ts                = strtotime($_ts);
        $form_data['meta']['publish-time'] = Date::format("Hi", $ts);
      }

      if ($form_data['type'] == 'none') {
        $index_file = true;
      }

      // @TODO, confirm "/page" is the best match pattern
      // e.g. "2-blog/_2013-04-11-a-hidden-page" will trigger (true)
      if (Pattern::endsWith($path, '/page')) {
        $index_file = true;
      }

      $errors = array();

      if (!$form_data['yaml']['title'] || $form_data['yaml']['title'] == '') {
        $errors['title'] = Localization::fetch('is_required');
      }

      $slug = ($form_data['meta']['slug'] === '/') ? '/' : Slug::make($form_data['meta']['slug']);

      if ($index_file) {
        // some different validation rules
        if ($slug == '') {
          $errors['slug'] = Localization::fetch('is_required');
        } else {
          if ($slug != $form_data['original_slug']) {
            if ($form_data['type'] == 'none') {
              $file  = $check_file = $content_root . "/" . $path . "/" . $slug . "/page." . $content_type;
              $folders = Statamic::get_content_tree($path, 1, 1, false, false, true);
              if (Statamic_Validate::folder_slug_exists($folders, $slug)) {
                $errors['slug'] = Localization::fetch('already_exists');
              }
            } else {
              $file     = $content_root . "/" . dirname($path) . "/page." . $content_type;
              $check_file = str_replace($form_data['original_slug'], $slug, $file);
              if (File::exists($check_file)) {
                $errors['slug'] = Localization::fetch('already_exists');
              }
            }

          }
        }
      } elseif (isset($form_data['type']) && $form_data ['type'] == 'none') {
        $file = $content_root . "/" . $path . "/" . $slug . "." . $content_type;
        if (File::exists($file)) {
          $errors['slug'] = Localization::fetch('already_exists');
        }
      } else {
        if (isset($form_data['new'])) {
          $entries = Statamic::get_content_list($path, null, 0, true, true);
        } else {
          $entries = Statamic::get_content_list(dirname($path), null, 0, true, true);
        }

        if ($slug == '') {
          $errors['slug'] = Localization::fetch('is_required');
        } else {
          // do we have this slug already?
          if (isset($form_data['new']) || $slug != $form_data['original_slug']) {
            if (Statamic_Validate::content_slug_exists($entries, $slug)) {
              $errors['slug'] = Localization::fetch('already_exists');
            }
          }
        }

        // generate slug & datestamp/number
        $datestamp = '';
        $timestamp = '';
        $numeric   = '';
        if ($form_data['type'] == 'date') {
          // STANDARDIZE INPUT
          $datestamp = $form_data['meta']['publish-date'];
          if ($datestamp == '') {
            $errors['datestamp'] = Localization::fetch('is_required');
          }

          if (Config::getEntryTimestamps()) {
            $timestamp = $form_data['meta']['publish-time'];
            if ($timestamp == '') {
              $errors['timestamp'] = Localization::fetch('is_required');
            }
          }
        } elseif ($form_data['type'] == 'number') {
          $numeric = $form_data['meta']['publish-numeric'];
          if ($numeric == '') {
            $errors['numeric'] = Localization::fetch('is_required');
          }
        }
      }

      if (sizeof($errors) > 0) {
        // REPOPULATE IF THERE IS AN ERROR
        if (isset($form_data['new'])) {
          $data['new'] = $form_data['new'];
        }

        $data['path']  = $path;
        $data['page']  = '';
        $data['title'] = $form_data['yaml']['title'];

        $folder        = $form_data['folder'];
        $data['folder']    = $form_data['folder'];
        $data['content']   = $form_data['content'];
        $data['content_raw'] = $form_data['content'];
        $data['type']    = $form_data['type'];
        $data['errors']    = $errors;

        $data['slug']      = $form_data['meta']['slug'];
        $data['full_slug']   = $form_data['full_slug'];
        $data['original_slug'] = $form_data['original_slug'];

        $data['original_datestamp'] = $form_data['original_datestamp'];
        $data['original_timestamp'] = $form_data['original_timestamp'];
        $data['original_numeric']   = $form_data['original_numeric'];

        if (isset($form_data['fieldset'])) {
          $data['fieldset'] = $form_data['fieldset'];
        }

        if (!$index_file) {
          if (isset($form_data['type']) && $form_data ['type'] != 'none') {
            $data['datestamp'] = strtotime($datestamp);
            $data['timestamp'] = strtotime($datestamp . " " . $timestamp);
            $data['numeric']   = $numeric;
          }
        }

        if (isset($form_data['yaml']['_template'])) {
          $data['_template'] = $form_data['yaml']['_template'];
        } else {
          $data['_template'] = '';
        }

        $data['templates'] = Theme::getTemplates();
        $data['layouts']   = Theme::getLayouts();

        $fields_data  = null;
        $content_root = Config::getContentRoot();

        // fieldset
        if ($data['type'] == 'none') {
          // load field set

          if (isset($data['fieldset'])) {
            $fieldset     = $data['fieldset'];
            $fs         = Statamic_Fieldset::load($fieldset);
            $fields_data    = $fs->get_data();
            $data['fields']   = isset($fields_data['fields']) ? $fields_data['fields'] : array();
            $data['fieldset'] = $fieldset;
          }
        } elseif ($data['type'] != 'none' && File::exists("{$content_root}/{$folder}/fields.yaml")) {

          $fields_raw  = File::get("{$content_root}/{$folder}/fields.yaml");
          $fields_data = YAML::Parse($fields_raw);
          if (isset($fields_data['_fieldset'])) {
            $fieldset     = $fields_data['_fieldset'];
            $fs         = Statamic_Fieldset::load($fieldset);
            $fields_data    = $fs->get_data();
            $data['fields']   = isset($fields_data['fields']) ? $fields_data['fields'] : array();
            $data['fieldset'] = $fieldset;
          }
        }

        if ($fields_data && isset($fields_data['fields'])) {
          $data['fields'] = $fields_data['fields'];
          // reload the fields data
          foreach ($data['fields'] as $key => $value) {
            if (isset($form_data['yaml'][$key])) {
              $data[$key] = $form_data['yaml'][$key];
            }
          }
        }

        /*
          |--------------------------------------------------------------------------
          | Status bar message
          |--------------------------------------------------------------------------
          |
          | Gawd this is awful. Can't wait to refactor this spaghetti.
          |
          */

        $data['status_message'] = (isset($data['new'])) ? Localization::fetch('new') : Localization::fetch('editing');
        $data['status_message'] .= ' ';

        if ($data['type'] === 'none' || ($data['type'] === 'none' && $original_slug !== 'page')) {
          $data['status_message'] .= Localization::fetch('page', null, true);
          $data['identifier'] = ($data['page'] === 'page') ? Path::pretty($data['folder']) : Path::pretty($data['full_slug']);
        } else {
          $data['status_message'] .= Localization::fetch('entry', null, true);
          $data['identifier'] = (isset($data['new'])) ? Path::pretty($folder . '/') : Path::pretty($data['full_slug']);
        }

        if (isset($data['new'])) $data['status_message'] .= ' ' . Localization::fetch('in', null, true);

        $template_list = array("publish");
        Statamic_View::set_templates(array_reverse($template_list));
        $admin_app->render(null, array('route' => 'publish', 'app' => $admin_app) + $data);

        return;
      }
    } else {
      // @TODO: Replace this garbage
      print "no form data";
    }
  } else {
    // @TODO: Replace this garbage too
    print "no form data";
  }

  $status    = array_get($form_data['yaml'], 'status', 'live');
  $status_prefix = Slug::getStatusPrefix($status);

  // if we got here, have no errors
  // save to original file if not new
  if (isset($form_data['new'])) {
    if ($form_data['type'] == 'date') {

      $date_or_datetime = Config::getEntryTimestamps() ? $datestamp . "-" . $timestamp : $datestamp;
      $file       = $content_root . "/" . $path . "/" . $status_prefix . $date_or_datetime . "-" . $slug . "." . $content_type;

    } elseif ($form_data['type'] == 'number') {
      $slug = $numeric . "." . $slug;
      $file = $content_root . "/" . $path . "/" . $status_prefix . $slug . "." . $content_type;

    } elseif ($form_data['type'] == 'none') {
      if (!preg_match(Pattern::NUMERIC, $slug, $matches)) {
        $numeric = Statamic::get_next_numeric_folder($path);
        $slug  = $numeric . "-" . $slug;
      }

      $file = $content_root . "/" . $path . "/" . $status_prefix . $slug . "/page." . $content_type;
      $file = Path::tidy($file);

      if (!File::exists(dirname($file))) {
        Folder::make(dirname($file));
      }

    } else {
      $file = $content_root . "/" . $path . "/" . $form_data['original_slug'] . "." . $content_type;
    }

    $folder = $path;

  } else {
    $file = Path::assemble(Config::getContentRoot(), $path) . '.' . $content_type;
  }

  // load the original yaml
  if (isset($form_data['new'])) {
    $file_data = array();
  } else {
    $page    = basename($path);
    $folder  = dirname($path);
    $file_data = Statamic::get_content_meta($page, $folder, true);
  }

  # Post-processing for Fieldtypes api
  if (isset($file_data['_fieldset'])) {

    # defined a fieldset in the front-matter
    $fs       = Statamic_Fieldset::load($file_data['_fieldset']);
    $fieldset_data  = $fs->get_data();
    $data['fields'] = $fieldset_data['fields'];
  } elseif (isset($fields_data['fields'])) {

    # fields.yaml controls the fields
    $data['fields'] = $fields_data['fields'];
  } elseif (isset($fields_data['_fieldset'])) {

    # using a fieldset
    $fieldset     = $fields_data['_fieldset'];
    $fs       = Statamic_Fieldset::load($fieldset);
    $fieldset_data  = $fs->get_data();
    $data['fields'] = $fieldset_data['fields'];
  } else {

    # not set.
    $data['fields'] = array();
  }

  /*
    |--------------------------------------------------------------------------
    | Check if file is writable
    |--------------------------------------------------------------------------
    |
    | We now have a file name. Let's check if we can write to this thing.
    | If not, throw an error page
    |
    */

  if (!Statamic::is_content_writable() || (File::exists($file) && !File::isWritable($file))) {
    $url = $admin_app->urlFor('error') . "?code=content_not_writable";
    $admin_app->redirect($url);
  }

  /*
    |--------------------------------------------------------------------------
    | Fieldset defaults
    |--------------------------------------------------------------------------
    |
    | We need to bring in the fieldset so we know what we're working with
    |
    */


  $fieldset     = null;
  $field_settings = array();
  if (count($data['fields']) < 1 && File::exists("{$content_root}/{$folder}/fields.yaml")) {
    $fields_raw  = File::get("{$content_root}/{$folder}/fields.yaml");
    $fields_data = YAML::Parse($fields_raw);

    if (isset($fields_data['fields'])) {

      #fields.yaml
      $field_settings = $fields_data['fields'];
    } elseif (isset($fields_data['_fieldset'])) {

      # using a fieldset
      $fieldset     = $fields_data['_fieldset'];
      $fs       = Statamic_Fieldset::load($fieldset);
      $fieldset_data  = $fs->get_data();
      $field_settings = $fieldset_data['fields'];
    } else {
      $field_settings = array();
    }
  } elseif (isset($form_data['type']) && $form_data['type'] == 'none') {
    if (isset($form_data['fieldset'])) {
      $fieldset = $form_data['fieldset'];

      $file_data['_fieldset'] = $fieldset;
      $fs           = Statamic_Fieldset::load($fieldset);
      $fields_data      = $fs->get_data();
      $field_settings     = $fields_data['fields'];
    }
  } else {
    $field_settings = array_get($data, 'fields', array());
  }

  /*
    |--------------------------------------------------------------------------
    | Check for empty checkbox fields
    |--------------------------------------------------------------------------
    |
    | Unchecked checkbox fields will not be included in the POST array due to
    | being unsuccessful, thus, we need to loop through all expected fields
    | looking for a checkbox type, and if it isn't in POST, set it to 0 manually
    |
    */

  foreach ($field_settings as $field => $settings) {
    if (isset($settings['type']) && $settings['type'] == 'checkbox' && !isset($form_data['yaml'][$field])) {
      $form_data['yaml'][$field] = 0;
    }
  }


  /*
    |--------------------------------------------------------------------------
    | Fieldtype Process Method
    |--------------------------------------------------------------------------
    |
    | Fieldtypes get the opportunity to process their own data.
    | That happens right here.
    |
    */

  foreach ($form_data['yaml'] as $field => $value) {
    if (isset($field_settings[$field]['type'])) {
      $file_data[$field] = Fieldtype::process_field_data($field_settings[$field]['type'], $value, $field_settings[$field], $field);
    } else {
      $file_data[$field] = $value;
    }
  }

  /*
    |--------------------------------------------------------------------------
    | Clean up data
    |--------------------------------------------------------------------------
    |
    | We make the assumption that all fields INSIDE the fieldset are editable
    | and all those OUTSIDE should be left alone. So we filter the submission
    | against the original fieldset and and wipe out empty fields to keep page 
    | variables clean. Not present = null = consistent data types = happy devs.
    |
    */
  // rd($file_data);
  // rd($field_settings);
  // rd($form_data['yaml']);

  foreach ($field_settings as $field => $settings) {
    $current_field = array_get($form_data['yaml'], $field, false);

    if (!$current_field || $current_field === '' || Helper::isEmptyArray($current_field)) {
      unset($file_data[$field]);
    }
  }

  unset($file_data['content'], $file_data['content_raw'], $file_data['last_modified']);

  if (isset($file_data['status'])) {
    unset($file_data['status']);
  }

  /*
    |--------------------------------------------------------------------------
    | Prep data & run hook
    |--------------------------------------------------------------------------
    |
    | Set up an array of useful data for the hook and then run that sucker.
    |
    */

  $publish_data = array('yaml' => $file_data, 'content' => $form_data['content'], 'file' => $file, 'new' => array_get($form_data, 'new', false));

  $publish_data      = Hook::run('control_panel', 'publish', 'replace', $publish_data, $publish_data);
  $original_publish_data = $publish_data;

  /*
    |--------------------------------------------------------------------------
    | Build and write content
    |--------------------------------------------------------------------------
    |
    | Let's create or update this file.
    |
    */

  $file_content = File::buildContent($publish_data['yaml'], $publish_data['content']);

  File::put(Path::assemble(BASE_PATH, $file), $file_content);

  /*
    |--------------------------------------------------------------------------
    | Rename/move file
    |--------------------------------------------------------------------------
    |
    | If the slug changed we'll need to rename the file accordingly.
    |
    */

  if (!isset($form_data['new'])) {

    $new_slug = ($form_data['meta']['slug'] === '/') ? '/' : Slug::make($form_data['meta']['slug']);

    // Date Entry
    if ($form_data['type'] == 'date') {

      // With Timestamps
      if (Config::getEntryTimestamps()) {
        $new_timestamp = $form_data['meta']['publish-time'];
        $new_datestamp = $form_data['meta']['publish-date'];
        $new_file    = Path::assemble($content_root, dirname($path), $status_prefix . $new_datestamp . "-" . $new_timestamp . "-" . $new_slug . "." . $content_type);

        // Without Timestamps
      } else {
        $new_datestamp = $form_data['meta']['publish-date'];
        $new_file    = Path::assemble($content_root, dirname($path), $status_prefix . $new_datestamp . "-" . $new_slug . "." . $content_type);
      }

      // Numerical Entry
    } elseif ($form_data['type'] == 'number') {
      $new_numeric = $form_data['meta']['publish-numeric'];
      $new_file  = Path::assemble($content_root, dirname($path), $status_prefix . $new_numeric . "." . $new_slug . "." . $content_type);

      // Pages
    } else {

      // Folder/page.md
      if ($index_file) {
        $new_file = str_replace($form_data['original_slug'], $status_prefix . $new_slug, $file);
      } else {

        // Regular page
        $new_file = $content_root . "/" . dirname($path) . "/" . $status_prefix . $new_slug . "." . $content_type;
      }
    }

    // ensure that both variables are coming from the same place
    $file   = Path::addStartingSlash($file);
    $new_file = Path::addStartingSlash($new_file);

    if ($file !== $new_file) {
      if ($index_file) {
        // If the page is an index file but not in a directory we want to rename the file not the parent directory.
        if (dirname($file) != dirname($new_file)) {
          File::rename(dirname($file), dirname($new_file));
        } else {
          File::rename($file, $new_file);
        }
      } else {
        File::rename($file, $new_file);
      }

      // file rename
      Hook::run('control_panel', 'move', null, null, array('old_file' => $file, 'new_file' => $new_file));
      $original_publish_data['file'] = $new_file;
    }
  }
  
  // find content and pass it in
  $use_file = (empty($new_file)) ? $file : $new_file;

  // hookyhook
  Hook::run('control_panel', 'post_publish', 'call', null, array(
    'file' => $use_file,
    'data' => $original_publish_data
  ));
    

  /*
    |--------------------------------------------------------------------------
    | Done. Let's redirect!
    |--------------------------------------------------------------------------
    |
    | Pages go back to the tree, entries to their respective Entry Listing
    | Or, if a custom return was specified, we'll go there.
    |
    */

  if ($form_data['type'] == 'none') {
    $app->flash('success', Localization::fetch('page_saved'));
  } else {
    $app->flash('success', Localization::fetch('entry_saved'));
  }

  $return = Request::post('return');

  if (Request::post('continue')) {
    $path = rtrim(Path::trimSlashes(str_replace(Config::getContentRoot(), '', Path::tidy($file))), '.' . Config::getContentType());
    $redirect_url = $admin_app->urlFor('publish') . '?path=' . $path;
    if (strpos(Request::getReferrer(), 'return=')) { // maintain the 'return' in the URL
      $return_uri = substr($return, strlen($app->request()->getRootUri()));
      $redirect_url .= '&return=' . $return_uri;
    }
    $app->redirect($redirect_url);
  } else {
    $app->redirect($return);
  }

});

// GET: DELETE ENTRY
$admin_app->map('/delete/entry', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);
  $content_root = Config::getContentRoot();
  $content_type = Config::getContentType();

  $entries = (array)Request::fetch('entries');
  $count   = count($entries);

  foreach ($entries as $path) {
    $file = $content_root . "/" . $path . "." . $content_type;

    /*
      |--------------------------------------------------------------------------
      | Delete Hook
      |--------------------------------------------------------------------------
      |
      | Runs the delete hook, passing the file path
      |
      */

    Hook::run('control_panel', 'delete', null, $file, $file);

    File::delete($file);
  }

  if ($count > 1) {
    $admin_app->flash('success', Localization::fetch('entries_deleted'));
  } else {
    $admin_app->flash('success', Localization::fetch('entry_deleted'));
  }

  $url = $admin_app->request()->getReferrer();
  $admin_app->redirect($url);

})->name('delete_entry')->via('GET', 'POST');;


// GET: DELETE PAGE
$admin_app->get('/delete/page', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $path = Path::assemble(BASE_PATH, Config::getContentRoot(), $admin_app->request()->get('path'));

  $type = $admin_app->request()->get('type');

  if ($type == "folder" && Folder::exists($path)) {

    Hook::run('control_panel', 'delete', null, $path . '/page', $path . '/page');
    Folder::delete($path);
    $admin_app->flash('success', Localization::fetch('page_deleted'));

  } else {

    if (!Pattern::endsWith($path, Config::getContentType())) {
      $path .= Config::getContentType();
    }

    if (File::exists($path)) {

      /*
      |--------------------------------------------------------------------------
      | Delete Hook
      |--------------------------------------------------------------------------
      |
      | Runs the delete hook, passing the file path
      |
      */

      Hook::run('control_panel', 'delete', null, $path, $path);
      File::delete($path);

      $admin_app->flash('success', Localization::fetch('page_deleted'));

    } else {
      $admin_app->flash('failure', Localization::fetch('page_unable_delete'));
    }
  }

  $admin_app->redirect($admin_app->urlFor('pages'));
})->name('delete_page');


// GET: PUBLISH
$admin_app->get('/publish', function () use ($admin_app) {

  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $content_root = Config::getContentRoot();
  $app      = \Slim\Slim::getInstance();

  $data   = array();
  $path   = Request::get('path');
  $new    = Request::get('new');
  $fieldset = Request::get('fieldset');
  $type   = Request::get('type');

  if ($path) {

    if ($new) {
      $data['new'] = 'true';
      $page    = '';
      $folder    = $path;

      $data['full_slug']   = dirname($path);
      $data['slug']    = '';
      $data['path']    = $path;
      $data['page']    = '';
      $data['title']     = '';
      $data['folder']    = $folder;
      $data['content']   = '';
      $data['content_raw'] = '';

      $data['datestamp'] = time();
      $data['timestamp'] = time();

      $data['original_slug']    = '';
      $data['original_datestamp'] = '';
      $data['original_timestamp'] = '';
      $data['original_numeric']   = '';

      if ($type == 'none') {
        $data['folder']  = $path;
        $data['full_slug'] = $path;
        $data['slug']    = 'page';
      }

    } else {
      $page   = basename($path);
      $folder = substr($path, 0, (-1 * strlen($page)) - 1);

      if (!Content::exists($page, $folder)) {
        $app->flash('error', Localization::fetch('content_not_found'));
        $url = $app->urlFor('pages');
        $app->redirect($url);

        return;
      }

      $data = Statamic::get_content_meta($page, $folder, true);

      // option to update
      $data = Hook::run('control_panel', 'form_content', 'replace', $data, $data);

      $data['title']        = isset($data['title']) ? $data['title'] : '';
      $data['slug']         = basename($path);
      $data['full_slug']      = $folder . "/" . $page;
      $data['path']         = $path;
      $data['folder']       = $folder;
      $data['page']         = $page;
      $data['type']         = 'none';
      $data['original_slug']    = '';
      $data['original_datestamp'] = '';
      $data['original_timestamp'] = '';
      $data['original_numeric']   = '';
      $data['datestamp']      = 0;

      if ($page == 'page') {
        $page = basename($folder);
        if ($page == '') $page = '/';
        $folder      = dirname($folder);
        $data['full_slug'] = $page;
      }
    }

    // Get/Set Status
    if ($data['slug'] === 'page') {
      $data['status'] = array_get($data, 'status', Slug::getStatus($data['folder']));
    } else {
      $data['status'] = array_get($data, 'status', Slug::getStatus($page));
    }

    if ($data['slug'] != 'page' && File::exists("{$content_root}/{$folder}/fields.yaml")) {

      $fields_raw  = file_get_contents("{$content_root}/{$folder}/fields.yaml");
      $fields_data = YAML::Parse($fields_raw);

      if (isset($fields_data['fields'])) {
        # fields.yaml controls the fields
        $data['fields'] = $fields_data['fields'];
      } elseif (isset($fields_data['_fieldset'])) {
        # using a fieldset
        $fieldset     = $fields_data['_fieldset'];
        $fs       = Statamic_Fieldset::load($fieldset);
        $fieldset_data  = $fs->get_data();
        $data['fields'] = $fieldset_data['fields'];
      } else {
        # not set.
        $data['fields'] = array();
      }

      $data['type'] = isset($fields_data['type']) && !is_array($fields_data['type']) ? $fields_data['type'] : $fields_data['type']['prefix'];

      // Slug
      if (Slug::isDraft($page)) {
        $slug = substr($page, 2);
      } elseif (Slug::isHidden($page)) {
        $slug = substr($page, 1);
      } else {
        $slug = $page;
      }

      if ($data['type'] == 'date') {
        if (Config::getEntryTimestamps() && Slug::isDateTime($page)) {

          $data['full_slug']      = $folder;
          $data['original_slug']    = substr($slug, 16);
          $data['slug']         = substr($slug, 16);
          $data['original_datestamp'] = substr($slug, 0, 10);
          $data['original_timestamp'] = substr($slug, 11, 4);
          if (!$new) {
            $data['datestamp'] = strtotime(substr($slug, 0, 10));
            $data['timestamp'] = strtotime(substr($slug, 0, 10) . " " . substr($slug, 11, 4));

            $data['full_slug'] = $folder . "/" . $data['original_slug'];
          }
        } else {
          $data['full_slug']      = $folder;
          $data['original_slug']    = substr($slug, 11);
          $data['slug']         = substr($slug, 11);
          $data['original_datestamp'] = substr($slug, 0, 10);
          $data['original_timestamp'] = "";
          if (!$new) {
            $data['datestamp'] = strtotime(substr($slug, 0, 10));
            $data['full_slug'] = $folder . "/" . $data['original_slug'];
            $data['timestamp'] = "0000";
          }
        }
      } elseif ($data['type'] == 'number') {
        if ($new) {
          $data['original_numeric'] = Statamic::get_next_numeric($folder);
          $data['numeric']      = Statamic::get_next_numeric($folder);
          $data['full_slug']    = $folder;
        } else {
          $numeric          = Slug::getOrderNumber($slug);
          $data['slug']       = substr($slug, strlen($numeric) + 1);
          $data['original_slug']  = substr($slug, strlen($numeric) + 1);
          $data['numeric']      = $numeric;
          $data['original_numeric'] = $numeric;
          $data['full_slug']    = $folder . "/" . $data['original_slug'];
        }
      }
    } else {


      if ($new) {
        if ($fieldset) {
          $fs         = Statamic_Fieldset::load($fieldset);
          $fields_data    = $fs->get_data();
          $data['fields']   = isset($fields_data['fields']) ? $fields_data['fields'] : array();
          $data['type']   = 'none';
          $data['fieldset'] = $fieldset;

        }
      } else {
        $fieldset = array_get($data, '_fieldset', Config::get('default_fieldset'));
        if ($fieldset) {
          $fs         = Statamic_Fieldset::load($fieldset);
          $fields_data    = $fs->get_data();
          $data['fields']   = array_get($fields_data, 'fields', array());
          $data['fieldset'] = $fieldset;
        }
        $data['type'] = 'none';
      }

      if (Slug::isDraft($page)) {
        $data['slug'] = substr($page, 2);
      } elseif (Slug::isHidden($page)) {
        $data['slug'] = substr($page, 1);
      } else {
        $data['slug'] = $page;
      }

      $data['original_slug'] = $page;
    }

  } else {
    print "NO PATH";
  }

  // We want to respect the Status field, but not run it through Fieldset::render()
  $data['status'] = ($new) ? array_get($data, 'fields:status:default', 'live') : $data['status'];
  unset($data['fields']['status']);

  // Content
  $content_defaults         = array_get($data, 'fields:content', array());
  $content_defaults['display']    = array_get($data, 'fields:content:display', 'Content');
  $content_defaults['type']     = array_get($data, 'fields:content:type', 'markitup');
  $content_defaults['required']   = (array_get($data, 'fields:content:required', false) === true) ? 'required' : '';
  $content_defaults['instructions'] = array_get($data, 'fields:content:instructions', '');
  $content_defaults['required']   = array_get($data, 'fields:content:required', false);
  $content_defaults['input_key']  = '';

  array_set($data, 'fields:content', $content_defaults);

  $data['full_slug'] = Path::tidy($data['full_slug']);

  /*
    |--------------------------------------------------------------------------
    | Status bar message
    |--------------------------------------------------------------------------
    |
    | Gawd this is awful. Can't wait to refactor this spaghetti.
    |
    */

  if ($data['type'] === 'none' || ($data['type'] === 'none' && $original_slug !== 'page')) {
    $data['status_message'] = (isset($new)) ? Localization::fetch('editing_page') : Localization::fetch('edit_page');
    $data['identifier']   = ($data['page'] === 'page') ? Path::pretty($data['folder']) : Path::pretty($data['full_slug']);
  } else {
    $data['status_message'] = (isset($new)) ? Localization::fetch('new_entry') : Localization::fetch('editing_entry');
    $data['identifier']   = (isset($new)) ? Path::pretty($folder . '/') : Path::pretty($data['full_slug']);
  }

  if ($new) $data['status_message'] .= ' ' . Localization::fetch('in', null, true);

  // Set the return URL
  if ($custom_return = Request::get('return')) {
    $data['return'] = $app->request()->getRootUri() . $custom_return;
  } else {
    $data['return'] = ($data['type'] == 'none')
      ? $app->urlFor('pages') . "?path=" . Path::addStartingSlash($folder)
      : $app->urlFor('entries') . "?path=" . Path::addStartingSlash($folder);
  }

  $data['templates'] = Theme::getTemplates();
  $data['layouts']   = Theme::getLayouts();

  $template_list = array("publish");
  Statamic_View::set_templates(array_reverse($template_list));

  /*
    |--------------------------------------------------------------------------
    | Hook: Can Publish
    |--------------------------------------------------------------------------
    |
    | Gives the ability to prevent editing/publishing.
    |
    */

  $can_edit = Hook::run('control_panel', 'can_publish', 'replace', true, $data);

  if (!$can_edit) {
    $admin_app->redirect($admin_app->urlFor('denied'));
  }

  $admin_app->render(null, array('route' => 'publish', 'app' => $admin_app) + $data);

})->name('publish');


// MEMBERS
// --------------------------------------------------------
$admin_app->get('/members', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $members     = Member::getList();
  $data['members'] = $members;

  $template_list = array("members");
  Statamic_View::set_templates(array_reverse($template_list));
  $admin_app->render(null, array('route' => 'members', 'app' => $admin_app) + $data);
})->name('members');


// POST: MEMBER
// --------------------------------------------------------
$admin_app->post('/member', function () use ($admin_app) {

  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  // Get member data from POST and config
  $submission  = $admin_app->request()->post('page');
  $submission  = (isset($submission['yaml']) && is_array($submission['yaml'])) ? $submission['yaml'] : array();
  $member_data = $admin_app->request()->post('member');
  $config    = YAML::parse(File::get(Config::getConfigPath() . '/bundles/member/fields.yaml'));
  $config    = (isset($config['fields']) && is_array($config['fields'])) ? $config['fields'] : array();

  // prepare submission
  array_walk_recursive($submission, function (&$item, $key) {
    // we don't need to special-chars a password
    if (strpos($key, '[password]') !== 0) {
      return;
    }
    $item = htmlspecialchars($item);
  });

  // check that everything's as we're expecting
  if (!isset($submission) || !is_array($submission) || !is_array($config)) {
    // something went wrong
    $admin_app->flash('error', Localization::fetch('error_form_submission'));
    $admin_app->redirect($admin_app->request()->getReferrer());
  }

  // prepare the submission for validation
  $username      = $submission['username'];
  $original_username = $member_data['original_name'];
  $is_new      = (isset($member_data['new']) && (bool)$member_data['new']);

  // validate
  $errors = Form::validate($submission, $config);

  // don't need username here anymore
  unset($submission['username']);

  // validate username if it was set
  if (!isset($errors['username']) && $username !== $original_username && !Member::isValidUsername($username)) {
    $errors['username'] = Localization::fetch('invalid_username');
  }

  // check password
  if (
    !isset($errors['password']) &&
    !isset($errors['password_confirmation']) &&
    isset($submission['password']) &&
    isset($submission['password_confirmation']) &&
    $submission['password'] &&
    $submission['password_confirmation'] &&
    $submission['password'] !== $submission['password_confirmation']
  ) {
    $errors['password'] = Localization::fetch('password_confirmation_does_not_match');
  }

  // check that username doesn't already exist
  if (!isset($errors['username']) && $username !== $original_username && Member::exists($username)) {
    $errors['username'] = Localization::fetch('username_already_exists');
  }

  // if no errors, make the member object, renaming if necessary
  $member = null;
  if (empty($errors)) {
    if ($is_new) {
      $member = new Member(array());
      $member->set('username', $username);
    } else {
      try {
        $member = Member::load($original_username);
        $member->rename($username);

        $member = Member::load($username);
      } catch (Exception $e) {
        $errors['username'] = $e->getMessage();
      }
    }
  }

  if ($errors) {
    // something isn't valid, no saving needed
    $admin_app->flash('error', Localization::fetch('error_form_submission'));

    Session::setFlash('member_old_values', $submission);
    Session::setFlash('member_errors', $errors);

    $admin_app->redirect($admin_app->request()->getReferrer());

    return;
  }

  // set variables
  foreach ($submission as $key => $value) {
    if ($key == 'password' && $value == '') {
      continue;
    }
      
    $field_config = array_get($config, $key, array());
      
    // only save values if save_value isn't false
    if (array_get($field_config, 'save_value', true)) {      
    
      // fieldtype processing if there's a type
      if (isset($config[$key]['type'])) {
        $value = Fieldtype::process_field_data($config[$key]['type'], $value, $config[$key], $key);
      }

      $member->set($key, $value);

    } else {
      $member->remove($key);
    }
  }

  // save member
  $member->save();

  // REDIRECT
  $admin_app->flash('success', Localization::fetch('member_saved'));

  $url = (CP_Helper::show_page('members')) ? $admin_app->urlFor('members') : $admin_app->urlFor('pages');

  $admin_app->redirect($url);
});


// GET: MEMBER
// --------------------------------------------------------
$admin_app->get('/member', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);
  $data = array();

  if (!Statamic::are_users_writable()) {
    $url = $admin_app->urlFor('error') . "?code=users_not_writable";
    $admin_app->redirect($url);
  }

  $name      = Session::getFlash('member_is_new', filter_input(INPUT_GET, 'name', FILTER_SANITIZE_STRING));
  $new       = Session::getFlash('member_is_new', filter_input(INPUT_GET, 'new', FILTER_SANITIZE_NUMBER_INT));
  $original_name = $name;

  if ($new) {
    $data['status_message'] = Localization::fetch('creating_member');
  } else {
    $data           = Member::getProfile($name, array('password'));
    $data['status_message'] = Localization::fetch('editing_member');
  }

  $data['fields']    = YAML::parse(Config::getConfigPath() . '/bundles/member/fields.yaml');
  $data['original_name'] = $original_name;
  $data['full_name']   = array_get($data, 'first_name', $name) . ' ' . array_get($data, 'last_name', '');

  // overwrite 'biography' as it needs to be 'biography_raw' here
  if (isset($data['fields']['fields']['biography'])) {
    $data['fields']['fields']['biography_raw'] = $data['fields']['fields']['biography'];
    unset($data['fields']['fields']['biography']);
  }

  $template_list = array("member");

  // check for flash data
  $errors   = Session::getFlash('member_errors', array());
  $old_values = Session::getFlash('member_old_values', array());

  // merge
  $data = $old_values + $data + array('_errors' => $errors, 'new' => $new);

  Statamic_View::set_templates(array_reverse($template_list));

  $admin_app->render(null, array('route' => 'members', 'app' => $admin_app) + $data);

})->name('member');


// GET: DELETE MEMBER
$admin_app->get('/deletemember', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $name = $admin_app->request()->get('name');
  if (Member::exists($name)) {
    $user = Auth::getMember($name);
    $user->delete();
  }

  // Redirect
  $admin_app->flash('info', Localization::fetch('member_deleted'));
  $url = $admin_app->urlFor('members');
  $admin_app->redirect($url);
})->name('deletemember');


// Account
// --------------------------------------------------------
$admin_app->get('/account', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $user   = Auth::getCurrentMember();
  $username = $user->get('username');

  $admin_app->redirect($admin_app->urlFor('member') . '?name=' . $username);

})->name('account');


// System
// --------------------------------------------------------
$admin_app->get('/system', function () use ($admin_app) {

  $redirect_to = Config::get('_admin_start_page', 'pages');
  $admin_app->redirect($admin_app->urlFor('security'));

})->name('system');


// Security
// --------------------------------------------------------
$admin_app->get('/system/security', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $template_list = array("security");
  Statamic_View::set_templates(array_reverse($template_list));

  $data = array();

  if (isCurlEnabled()) {

    $user   = Auth::getCurrentMember();
    $username = $user->get('username');

    $tests = array(
      '_app'                       => Localization::fetch('security_app_folder'),
      '_config'                      => Localization::fetch('security_config_folder'),
      '_config/settings.yaml'              => Localization::fetch('security_settings_files'),
      '_config/users/' . $username . '.yaml'       => Localization::fetch('security_user_files'),
      Config::getContentRoot()               => Localization::fetch('security_content_folder'),
      Config::getTemplatesPath() . 'layouts/default.php' => Localization::fetch('security_template_files'),
      '_logs'                      => Localization::fetch('security_logs_folder')
    );

    $site_url = 'http://' . $_SERVER['HTTP_HOST'] . '/';

    foreach ($tests as $url => $message) {
      $test_url = $site_url . $url;

      $http = curl_init($test_url);
      curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($http, CURLOPT_TIMEOUT, 3);
      $result    = curl_exec($http);
      $http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
      curl_close($http);

      $data['system_checks'][$url]['status_code'] = $http_status;
      $data['system_checks'][$url]['status']    = $http_status !== 200 ? 'good' : 'warning';
      $data['system_checks'][$url]['message']   = $message;
    }
  }
  $data['users'] = Member::getList();

  $admin_app->render(null, array('route' => 'security', 'app' => $admin_app) + $data);
})->name('security');


// Logs
// --------------------------------------------------------
$admin_app->get('/system/logs', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $template_list = array("logs");
  Statamic_View::set_templates(array_reverse($template_list));

  $data          = array();
  $data['enabled']     = Config::get("_log_enabled", false);
  $data['raw_path']    = Config::get("_log_file_path");
  $data['prefix']    = Config::get("_log_file_prefix");
  $data['log_level']   = Config::get("_log_level");
  $data['time_format']   = Config::get("_time_format");
  $data['logs']      = array();
  $data['logs_exist']  = false;
  $data['records_exist'] = false;
  $data['log_items']   = 0;
  $data['load_date']   = Date::format("Y-m-d");
  $data['log']       = array();
  $data['filter']    = '';
  $data['logs_writable'] = false;

  // determine actual path
  $data['path'] = $data['raw_path'];
  if (!in_array(substr($data['raw_path'], 0, 1), array("/", "."))) {
    $data['path'] = BASE_PATH . DIRECTORY_SEPARATOR . $data['raw_path'];
  }

  // is log folder writable?
  if (is_writable($data['path'])) {
    $data['logs_writable'] = true;
  }

  // do any logs exist here?
  try {
    $filename_regex = "/^" . $data['prefix'] . "_(\d{4})-(\d{2})-(\d{2})/i";
    $dir      = opendir($data['path']);

    if (!$dir) {
      throw new Exception("Directory not found");
    }

    while (false !== ($file = readdir($dir))) {
      if (!preg_match($filename_regex, $file, $matches)) {
        // no match, nothing to see here
        continue;
      }

      $data['logs'][$matches[1] . "-" . $matches[2] . "-" . $matches[3]] = array(
        "date"    => Date::format(Config::getDateFormat(), $matches[1] . "-" . $matches[2] . "-" . $matches[3]),
        "raw_date"  => $matches[1] . "-" . $matches[2] . "-" . $matches[3],
        "filename"  => $file,
        "full_path" => $data['path'] . DIRECTORY_SEPARATOR . $file
      );

      // we have found at least one valid log
      $data['logs_exist'] = true;
    }

    ksort($data['logs']);

    closedir($dir);

    // flip the order of logs
    $data['logs'] = array_reverse($data['logs']);
  } catch (Exception $e) {
    // no logs exist
    $data['logs_exist'] = false;
  }

  // filter
  $match  = array('DEBUG', 'INFO', 'WARN', 'ERROR', 'FATAL');
  $filter = filter_input(INPUT_GET, 'filter');
  if ($filter) {

    switch (strtolower($_GET['filter'])) {
      case 'debug':
        $match      = array('DEBUG');
        $data['filter'] = 'debug';
        break;

      case 'info':
        $match      = array('INFO');
        $data['filter'] = 'info';
        break;

      case 'info+';
        $match      = array('INFO', 'WARN', 'ERROR', 'FATAL');
        $data['filter'] = 'info+';
        break;

      case 'warn':
        $match      = array('WARN');
        $data['filter'] = 'warn';
        break;

      case 'warn+';
        $match      = array('WARN', 'ERROR', 'FATAL');
        $data['filter'] = 'warn+';
        break;

      case 'error':
        $match      = array('ERROR');
        $data['filter'] = 'error';
        break;

      case 'error+';
        $match      = array('ERROR', 'FATAL');
        $data['filter'] = 'error+';
        break;

      case 'fatal':
        $match = array('FATAL');
        break;
    }
  }

  // parse out logs, filtering the logs we want
  if ($data['logs_exist']) {
    $logs = array_values($data['logs']);

    // check for a log file to capture
    $load_date     = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
    $data['load_date'] = ($load_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) ? $_GET['date'] : $logs[0]['raw_date'];

    // load log
    try {
      $raw_log = file_get_contents($data['logs'][$data['load_date']]['full_path']);

      // parse through log
      $raw_log_lines   = explode(PHP_EOL, trim($raw_log));
      $data['log_items'] = count($raw_log_lines);

      // check for existing but empty log files
      if ($data['log_items'] === 1 && trim($raw_log) === "") {
        $data['log_items'] = 0;
      }

      foreach ($raw_log_lines as $line) {
        $log = explode("|", $line);

        if (!in_array($log[0], $match)) {
          continue;
        }

        array_push($data['log'], $log);
      }

      $data['records_exist'] = (bool)count($data['log']);
      uksort($data['log'], array('Helper', 'compareValues'));  // sort the logs which may be out of order
      $data['log'] = array_reverse($data['log']);
    } catch (Exception $e) {
      // no extra steps needed
    }
  }

  $admin_app->render(null, array('route' => 'logs', 'app' => $admin_app) + $data);
})->name('logs');




// Static Site Generator/Exporter
// --------------------------------------------------------
$admin_app->get('/system/export', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $template_list = array('export');
  Statamic_View::set_templates(array_reverse($template_list));

  $data = array(
    'folder_exists' => Addon::getAPI('generator')->folderExists()
  );

  $admin_app->render(null, array('route' => 'export', 'app' => $admin_app)+$data);
})->name('export');


$admin_app->get('/system/export/generate', function() use($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  Addon::getAPI('generator')->copyAssets();

  $result = Addon::getAPI('generator')->generateFileList();

  $response = $admin_app->response();
  $response['Content-Type'] = 'application/json';
  $response->status(200);
  $response->body(json_encode($result));
})->name('generatesite');


$admin_app->get('/system/export/generatefile', function() use($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $url = Request::get('url');

  $result = Addon::getAPI('generator')->generatePage($url);

  $response = $admin_app->response();
  $response['Content-Type'] = 'application/json';
  $response->status(200);
  $response->body(json_encode($result));
})->name('generatefile');

$admin_app->get('/system/export/download', function() use($admin_app) {


  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $result = Addon::getAPI('generator')->download();
})->name('downloadsite');


// Uploads
// --------------------------------------------------------
$admin_app->post('/upload', function () use ($admin_app) {

  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $files = _Upload::uploadBatch();

  $admin_app->contentType('application/json');
  echo json_encode(array('files' => $files));

})->name('upload');


// GET: IMAGES
// DEPRICATED in 1.3
// --------------------------------------------------------
$admin_app->get('/images', function () use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $path = $admin_app->request()->get('path');

  $image_list = glob($path . "*.{jpg,jpeg,gif,png}", GLOB_BRACE);
  $images   = array();

  if (count($image_list) > 0) {
    foreach ($image_list as $image) {
      $images[] = array(
        'thumb' => '/' . $image,
        'image' => '/' . $image
      );
    }
  }

  echo json_encode($images);

})->name('images');


// Files
// --------------------------------------------------------

// List files
$admin_app->get('/files', function() use ($admin_app){
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  if ( ! $config = Request::get('config')) {
    die('No config provided');
  }

  $config = unserialize(Helper::decrypt($config));
  $destination = $config['destination'];

  $files = Addon::getAPI('file')->getServerFiles($config, $destination);
  $modal = Addon::getAPI('file')->generateModal($files, $destination);

  echo $modal;

})->name('files');


// Delete file
$admin_app->get('/file/delete', function() use ($admin_app){
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $result = Addon::getAPI('file')->deleteFile();

  $response = $admin_app->response();
  $response['Content-Type'] = 'application/json';
  $response->status(200);
  $response->body(json_encode($result));

})->name('delete_file');




/*
|--------------------------------------------------------------------------
| Hook: Add Routes
|--------------------------------------------------------------------------
|
| Allows add-ons to add their own hooks to the control panel.
|
*/

Hook::run('control_panel', 'add_routes');


// GET: 404
// --------------------------------------------------------
$admin_app->notFound(function () use ($admin_app) {

  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $admin_app->flash('error', Localization::fetch('admin_404'));
  $redirect_to = Config::get('_admin_404_page', $admin_app->urlFor('pages'));
  $admin_app->redirect($redirect_to);

});
