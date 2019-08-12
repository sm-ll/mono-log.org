<div id="subnav">
  <ul>
    <li><a href="<?php echo $app->urlFor("raven") ?>" class="active"><?php echo Localization::fetch('Overview')?></a></li>
    <?php foreach ($formsets as $name => $values): ?>
      <li><a href="<?php echo $app->urlFor("raven") . '/' . $name ?>"><?php echo Slug::prettify($name) ?></a></li>
    <?php endforeach ?>
  </ul>
</div>

<div class="container">

  <div id="status-bar" class="web">
    <div class="status-block">
      <span class="muted"><?php echo Localization::fetch('raven_forms')?></span>
    </div>
  </div>

  <div class="section">
    <table class="simple-table metrics">
      <tbody>
        <tr>
          <?php foreach ($formsets as $name => $config): ?>
            <td>
              <a href="<?php echo $app->urlFor("raven") . '/' . $name ?>">
                <div class="label"><?php echo $name ?> <?php echo Localization::fetch('submissions')?></div>
                <div class="number"><?php echo number_format(count(array_get($files, $name)), 0) ?></div>
              </a>
            </td>
          <?php endforeach ?>
        </tr>
      </tbody>
    </table>
  </div>
</div>