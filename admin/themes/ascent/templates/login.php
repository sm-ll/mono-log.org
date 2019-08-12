<div id="login-wrapper">

  <div class="logo">&#xf003;</div>
  <div id="login-form">

    <form method="post" action="<?php print $app->urlFor('login'); ?>">
      <div class="login-row">
        <input type="text" class="text username" id="login-username" placeholder="<?php echo Localization::fetch('username')?>" name="login[username]" />
      </div>
      <div class="login-row">
        <input type="password" class="text password" id="login-password" placeholder="<?php echo Localization::fetch('password')?>" name="login[password]" />
      </div>
      <div class="submit-row">
        <input type="submit" class="btn btn-submit" id="login-submit" value="<?php echo Localization::fetch('login')?>" />
      </div>
    </form>
  </div>

</div>

<?php if ((isset($errors) && sizeof($errors) > 0)): ?>
<script type="text/javascript">

  <?php if (isset($errors['error'])): ?>

    $("#login-form").delay(50).effect( "shake" );

  <?php elseif (isset($errors['encrypted'])): ?>

    alertify.log("<?php print $errors['encrypted']; ?>");

  <?php endif ?>

</script>
<?php endif ?>