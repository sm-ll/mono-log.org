<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">
  <title><?php echo Config::getSiteName(); ?> <?php echo Localization::fetch('login')?></title>
  <link rel="stylesheet" href="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path']) ?>css/ascent.min.css">
  <link rel="icon" href="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>img/favicon.png" sizes="16x16" type="img/png" />
  <script type="text/javascript" src="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>js/ascent.min.js"></script>
  <?php echo Hook::run('control_panel', 'add_to_head', 'cumulative') ?>
</head>
<body id="login">
    <div id="login-wrapper">
      <div class="logo">&#xf003;</div>
      <div id="login-form">
        <form>
            <h1><?php echo Localization::fetch('offline')?></h1>
        </form>
      </div>
    </div>
  <?php echo Hook::run('control_panel', 'add_to_foot', 'cumulative') ?>
</body>
</html>