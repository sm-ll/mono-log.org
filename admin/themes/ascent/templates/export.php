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
      <span class="muted"><?php echo Localization::fetch('static_site_generator')?></span>
    </div>
    <ul>
      <li>
        <a href="<?php print $app->urlFor('generatesite'); ?>" id="generate-site">
          <span class="ss-icon">refresh</span>
          <?php echo Localization::fetch('generate_html')?>
        </a>
      </li>
      <?php if ($folder_exists): ?>
      <li>
        <a href="<?php print $app->urlFor('downloadsite'); ?>">
          <span class="ss-icon">download</span>
          <?php echo Localization::fetch('download_site')?>
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </div>

  <script type="text/html" id="generated-files-tmpl">
    <h2><?php echo Localization::fetch('generated_files')?></h2>
    <div class="section">
      <table class="simple-table static-file-list">
        <thead>
          <tr>
            <th>URL</th>
            <th><?php echo Localization::fetch('file')?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <% _.each(files, function(file){ %>
          <tr data-url="<%= file.url %>">
            <td><%= file.url %></td>
            <td><%= file.path %></td>
            <td class="status"><span class="ss-icon">clock</span></td>
          </tr>
          <% }); %>
        </tbody>
      </table>
    </div>
  </script>

  <div id="generated-files-wrap"></div>
</div>
