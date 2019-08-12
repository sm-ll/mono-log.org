<div id="subnav">
  <ul>
  <li><a href="<?php echo $app->urlFor("raven") ?>"><?php echo Localization::fetch('Overview')?></a></li>
    <?php foreach ($formsets as $name => $values): ?>
      <li><a href="<?php echo $app->urlFor("raven") . '/' . $name ?>" <?php if ($formset['name'] === $name): ?> class="active"<?php endif ?> ><?php echo Slug::prettify($name) ?></a></li>
    <?php endforeach ?>
  </ul>
</div>

<div class="container">

  <div id="status-bar" class="web">
    <div class="status-block">
      <strong><?php echo Slug::prettify($formset['name']) ?></strong> <span class="muted"><?php echo Localization::fetch('form', null, true) ?> <?php echo Localization::fetch('submissions', null, true)?>
    </div>
    <ul>
      <li>
        <a href="<?php echo $app->urlFor("raven") . '/' . $name ?>/export">
          <span class="ss-icon">downloadfile</span>
          <?php echo Localization::fetch('export_csv')?>
        </a>
      </li>
    </ul>
  </div>
  
  <?php if ($metrics): ?>
  <div class="section">
    <table class="simple-table metrics">
      <tbody>
        <tr>
          <?php foreach ($metrics as $metric): ?>
            <td>
              <div class="label"><?php echo $metric['label'] ?></div>
              <?php if ( ! is_array($metric['metrics'])): ?>
                <div class="number"><?php echo $metric['metrics'] ?></div>
              <?php else: ?>
                <ul class="metric-list">
                <?php foreach ($metric['metrics'] as $key => $value): ?>
                  <li>
                    <div class="list-label"><?php echo $key ?></div>
                    <div class="list-value"><?php echo $value ?></div>
                  </li>
                <?php endforeach ?>
                </ul>
              <?php endif ?>
            </td>
          <?php endforeach ?>
        </tr>
      </tbody>
    </table>
  </div>
  <?php endif ?>
  
  <div class="section">
    <table class="simple-table sortable">
      <thead>
        <tr>
          <th class="checkbox-col"></th>
          <?php foreach ($fields as $field => $name): ?>
            <th><?php echo $name ?></th>
          <?php endforeach ?>
        </t>
      </thead>
      <tbody>
        <?php foreach ($files as $file): ?>
          <tr>
            <th class="checkbox-col"></th>
            <?php foreach ($fields as $field => $name): ?>
              <td><?php echo array_get($file, 'fields:'.$field) ?></td>
            <?php endforeach ?>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
