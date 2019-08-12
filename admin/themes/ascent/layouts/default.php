<?php
  $current_user = Auth::getCurrentMember();
  $name = $current_user->get('name');
?><!doctype html>
<html lang="<?php echo Config::getCurrentLanguage(); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">
  <title>Statamic Control Panel</title>
  <?php if ( ! Config::get('disable_google_fonts', false)) { ?>
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Raleway:400,700|Open+Sans:400italic,400,600" />
  <?php } ?>  
  <link rel="stylesheet" href="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path']) ?>css/ascent.min.css">
  <link rel="shortcut icon" href="<?php print Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>img/favicon.ico" />
  <script>
      var transliterate = <?php echo json_encode(Config::get('custom_transliteration', array())); ?>;
  </script>
  <script>
      var content_type = "<?php echo Config::getContentType(); ?>";
  </script>
  <script type="text/javascript" src="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>js/ascent.min.js?v=1.8.2"></script>
  <script type="text/javascript">
	  Statamic.triggerUrl = "<?php echo URL::prependSiteRoot('TRIGGER'); ?>";
  </script>
  <?php echo Hook::run('control_panel', 'add_to_head', 'cumulative') ?>
</head>
<body id="<?php echo $route; ?>">
  <div id="wrap">
    <div id="main">
      <div id="control-bar" class="clearfix">
        <ul class="pull-left">
          <li id="statamic-logo">
            <a href="<?php echo $app->urlFor("dashboard"); ?>">statamic</a>
          </li>

          <?php if (CP_Helper::show_page('dashboard', false)): ?>
          <li id="item-content">
            <a href="<?php echo $app->urlFor("dashboard"); ?>"<?php if ($route === "dashboard"):?> class="active"<?php endif ?>>
              <span class="ss-icon">thumbnails</span>
              <span class="title"><?php echo Localization::fetch('dashboard')?></span>
            </a>
          </li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('pages', true)): ?>
          <li id="item-content">
            <a href="<?php echo $app->urlFor("pages"); ?>"<?php if ($route === "pages" || $route === "publish" || $route === "entries"):?> class="active"<?php endif ?>>
              <span class="ss-icon">textfile</span>
              <span class="title"><?php echo Localization::fetch('content')?></span>
            </a>
          </li>
        <?php endif ?>

          <?php if (CP_Helper::show_page('members', true)): ?>
          <li id="item-members">
            <a href="<?php echo $app->urlFor("members"); ?>"<?php if ($route === "members"):?> class="active"<?php endif ?>>
              <span class="ss-icon">users</span>
              <span class="title"><?php echo Localization::fetch('members')?></span>
            </a>
          </li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('system', true)): ?>
          <li id="item-system">
            <a href="<?php echo $app->urlFor("security"); ?>"<?php if ($route === "security" || $route == "logs"):?> class="active"<?php endif ?>>
              <span class="ss-icon">settings</span>
              <span class="title"><?php echo Localization::fetch('system')?></span>
            </a>
          </li>
          <?php endif ?>


          <?php foreach (CP_Helper::addon_nav_items() as $item): ?>

            <li id="item-<?php echo $item ?>">
              <a href="<?php echo URL::assemble($app->request()->getRootUri(), $item); ?>"<?php if ($route === $item):?> class="active"<?php endif ?>>
                <span class="ss-icon">
                  <?php if (Localization::fetch('nav_icon_' . $item) !== 'nav_icon_' . $item): ?>
                    <?php echo Localization::fetch('nav_icon_' . $item)?>
                  <?php endif ?>
                </span>
                <span class="title"><?php echo Localization::fetch('nav_title_' . $item)?></span>
              </a>
            </li>

          <?php endforeach; ?>

        </ul>

        <ul class="pull-right secondary-controls">

          <?php if (CP_Helper::show_page('account', true)): ?>
          <li>
            <a href="<?php echo $app->urlFor("member")."?name={$name}"; ?>">
              <img src="<?php echo $current_user->getGravatar(52) ?>" height="26" width="26" class="avatar" />
              <span class="name"><?php echo Localization::fetch('account') ?></span>
            </a>
          </li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('view_site', true)): ?>
          <li>
            <a href="<?php echo $app->config['_site_root']; ?>">
              <span class="ss-icon">link</span>
              <span class="title"><?php echo Localization::fetch('view_site')?></span>
            </a>
          </li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('logout', true)): ?>
          <li>
            <a href="<?php echo $app->urlFor("logout"); ?>">
              <span class="ss-icon">logout</span>
              <span class="title"><?php echo Localization::fetch('logout')?></span>
            </a>
          </li>
          <?php endif ?>

        </ul>
      </div>
      <?php echo $_html; ?>
  </div>
</div>
<div id="footer">
  <a href="http://statamic.com">Statamic</a> v<?php print STATAMIC_VERSION ?>
  <span id="version-check">
  <?php if (Pattern::isValidUUID($app->config['_license_key'])): ?>

    <?php if (isset($app->config['latest_version']) && $app->config['latest_version'] <> '' && STATAMIC_VERSION < $app->config['latest_version']): ?>
      <a href="https://store.statamic.com/account"><?php echo Localization::fetch('update_available')?>: v<?php echo $app->config['latest_version']; ?></a>
    <?php else: ?>
      <?php echo Localization::fetch('up_to_date')?>
    <?php endif ?>
  <?php else: ?>
    <a href="http://store.statamic.com"><?php echo Localization::fetch('site_unlicensed')?></a>
  <?php endif ?>
  </span>
</div>

<script type="text/javascript">

  <?php if ($flash['success']): ?>
    alertify.log("<?php print $flash['success']; ?>");
  <?php endif ?>

  <?php if ($flash['error']): ?>
    alertify.log("<?php print $flash['error']; ?>");
  <?php endif ?>
</script>

<?php echo Hook::run('control_panel', 'add_to_foot', 'cumulative') ?>
</body>
</html>
