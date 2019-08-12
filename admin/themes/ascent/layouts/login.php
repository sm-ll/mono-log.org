<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">
  <title><?php echo Config::getSiteName(); ?> <?php echo Localization::fetch('login')?></title>
  <?php if ( ! Config::get('disable_google_fonts', false)) { ?>
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Raleway:400,700|Open+Sans:400italic,400,600" />
  <?php } ?>
  <link rel="stylesheet" href="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path']) ?>css/ascent.min.css">
  <link rel="icon" href="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>img/favicon.png" sizes="16x16" type="img/png" />
  <script type="text/javascript" src="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>js/ascent.min.js"></script>
  <?php echo Hook::run('control_panel', 'add_to_head', 'cumulative') ?>
</head>
<body id="login">
  <?php echo $_html; ?>
  <?php echo Hook::run('control_panel', 'add_to_foot', 'cumulative') ?>
</body>
</html>
