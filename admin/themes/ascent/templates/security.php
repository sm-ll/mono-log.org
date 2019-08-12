<div id="subnav">
  <ul>
    <?php if (CP_Helper::show_page('security', true)): ?>
    <li><a href="<?php echo $app->urlFor("security"); ?>"<?php if ($route === "security"):?> class="active"<?php endif ?>><?php echo Localization::fetch('security_status')?></a></li>
    <?php endif ?>

    <?php if (CP_Helper::show_page('logs', true)): ?>
    <li><a href="<?php echo $app->urlFor("logs"); ?>"<?php if ($route === "logs"):?> class="active"<?php endif ?>><?php echo Localization::fetch('logs')?></a></li>
    <?php endif ?>

    <?php if (CP_Helper::show_page('export', true)): ?>
    <li><a href="<?php echo $app->urlFor("export"); ?>"<?php if ($route === "export"):?> class="active"<?php endif ?>><?php echo Localization::fetch('export_to_html')?></a></li>
    <?php endif ?>
  </ul>
</div>

<div class="container">

  <div id="status-bar" class="web">
    <div class="status-block">
      <span class="muted"><?php echo Localization::fetch('security_status')?></span>
    </div>
  </div>

  <h2 class="web"><?php echo Localization::fetch('security_files_security_check')?></h2>
  <div class="section">
    <?php if (isset($system_checks) && is_array($system_checks) && count($system_checks) > 0): ?>
    <table class="simple-table sortable table-security">
      <thead>
        <tr>
          <th><?php echo Localization::fetch('folder_file')?></th>
          <th><?php echo Localization::fetch('action_required')?></th>
          <th class="align-right"><?php echo Localization::fetch('secure_status')?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($system_checks as $asset => $data): ?>
        <?php extract($data); ?>
        <tr>
          <td><?php print $asset ?></td>
          <td><?php if ($status_code === 200): ?><?php echo $message?><?php else:?><span class="subtle"><?php echo Localization::fetch('none')?></span><?php endif ?></td>
          <td class="align-right"><?php if ($status_code !== 200): ?><?php echo Localization::fetch('secure')?> <span class="ss-icon">checkclipboard</span> <?php else: ?><?php echo Localization::fetch('unsecure')?> <span class="ss-icon">warning</span> <?php endif ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <h2 class="web"><?php echo Localization::fetch('user_accounts_security_check')?></h2>
  <div class="section">
    <table class="simple-table table-security">
      <thead>
        <tr>
          <th><?php echo Localization::fetch('members')?></th>
          <th><?php echo Localization::fetch('action_required')?></th>
          <th class="align-right"><?php echo Localization::fetch('password_status')?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $username => $user): ?>
        <tr>
          <td class="title <?php print $status ?>"><a href="../member?name=<?php print $username ?>"><?php print $username ?></a></td>
          <td><?php if ($status == 'warning'): ?><em><?php echo Localization::fetch('action_encrypt_password')?></em><?php else:?><span class="subtle"><?php echo Localization::fetch('none')?></span><?php endif ?></td>
          <td class="align-right">
            <?php if ($user->hasHashedPassword()): ?>
              <?php echo Localization::fetch('encrypted')?> <span class="ss-icon">checkclipboard</span>
            <?php else: ?>
              <?php echo Localization::fetch('unencrypted')?> <span class="ss-icon">warning</span>
            <?php endif ?>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
    <?php else: ?>

    <p><?php echo Localization::fetch('curl_needed')?></p>

    <?php endif; ?>
  </div>
</div>
