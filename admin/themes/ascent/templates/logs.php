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
      <span class="muted"><?php echo Localization::fetch('site_logs')?></span>
    </div>
  </div>


  <div class="section">
    <div class="section-header content">

      <?php if ($enabled && !$logs_writable): ?>
        <p class="alert">
          <?php echo Localization::fetch('log_file')?> (<?php echo $path; ?>) <?php echo Localization::fetch('log_unwritable')?>
        </p>
      <?php endif; ?>

      <p>
        <?php echo ($enabled) ? Localization::fetch('logging_yes') : Localization::fetch('logging_no'); ?>
        <?php if ($enabled): ?>
          <?php echo Localization::fetch('log_messages')?> <strong><?php echo $log_level; ?></strong>
        <?php else: ?>
          <?php echo Localization::fetch('log_turning_on')?>
        <?php endif; ?>
      </p>
    </div>

    <div class="content">
    <?php if ($logs_exist): ?>
      <form method="get" id="date-submit" class="log-controls">
        <p>
          <span>
            <?php echo Localization::fetch('showing')?>
            <select id="filter-chooser" name="filter">
              <option value=""><?php echo Localization::fetch('messages_all')?></option>
              <optgroup label="----------------">
                <option value="debug"<?php echo ($filter === "debug") ? ' selected="selected"' : ''?>><?php echo Localization::fetch('messages_debug')?></option>
                <option value="info"<?php echo ($filter === "info") ? ' selected="selected"' : ''?>><?php echo Localization::fetch('messages_info')?></option>
                <option value="warn"<?php echo ($filter === "warn") ? ' selected="selected"' : ''?>><?php echo Localization::fetch('messages_warn')?></option>
                <option value="error"<?php echo ($filter === "error") ? ' selected="selected"' : ''?>><?php echo Localization::fetch('messages_error')?></option>
                <option value="fatal"<?php echo ($filter === "fatal") ? ' selected="selected"' : ''?>><?php echo Localization::fetch('messages_fatal')?></option>
              </optgroup>
              <optgroup label="----------------">
                <option value="info+"<?php echo ($filter === "info+") ? ' selected="selected"' : ''?>><?php echo Localization::fetch('info_plus')?></option>
                <option value="warn+"<?php echo ($filter === "warn+") ? ' selected="selected"' : ''?>><?php echo Localization::fetch('warn_plus')?></option>
                <option value="error+"<?php echo ($filter === "error+") ? ' selected="selected"' : ''?>><?php echo Localization::fetch('error_plus')?></option>
              </optgroup>
            </select>
          </span>
          <span>
            <?php echo Localization::fetch('messages_happened')?>
            <select id="date-chooser" name="date">
              <?php
              foreach ($logs as $date => $info) {
                $selected = ($date == $load_date) ? ' selected="selected"' : ''?>
                ?>
                <option value="<?php echo $date; ?>"<?php echo $selected; ?>><?php echo $info['date']; ?></option>
                <?php
              }
              ?>
            </select>
            <input type="submit" value="Go" />
          </span>
        </p>
      </form>
    </div>
  </div>

    <?php if ($records_exist): ?>
    <div class="section">
      <table class="table-log log-sortable log">
        <thead>
          <tr>
            <th class="level"><?php echo Localization::fetch('messages_level')?></th>
            <th class="when"><?php echo Localization::fetch('messages_when')?></th>
            <th class="what" colspan="3"><?php echo Localization::fetch('messages_what')?></th>
            <th><?php echo Localization::fetch('messages_page')?></th>
            <th><?php echo Localization::fetch('messages_message')?></th>
          </tr>
        </thead>

        <tbody>
        <?php foreach ($log as $row): ?>
          <tr class="level-<?php echo strtolower($row[0]); ?>">
            <th class="level-<?php echo strtolower($row[0]); ?>" title="By <?php echo $row[5]; ?>"><?php echo ucwords(strtolower($row[0])); ?></th>
            <td class="when" data-fulldate="<?php echo strtotime($row[1]); ?>"><?php echo Date::format($time_format, $row[1]); ?></td>
            <td class="what"><?php echo $row[2]; ?></td>
            <td class="colon">:</td>
            <td class="specifically"><?php echo $row[3]; ?></td>
            <td><a href="<?php echo $row[4]; ?>"><?php echo $row[4]; ?></a></td>
            <td><?php echo Parse::markdown($row[7]); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <ul class="small-screen-log log">
      <?php foreach ($log as $row): ?>
        <li class="level-<?php echo strtolower($row[0]); ?>">
          <strong class="level level-<?php echo strtolower($row[0]); ?>"><?php echo ucwords(strtolower($row[0])); ?></strong>

          <?php echo Parse::markdown($row[7]); ?>

          <h6>
            <?php echo Date::format($time_format, $row[1]); ?> ·
            <?php echo $row[2]; ?>: <?php echo $row[3]; ?> ·
            <a href="<?php echo $row[4]; ?>"><?php echo $row[4]; ?></a>
          </h6>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
      <?php if (trim($filter)): ?>
        <p><?php echo Localization::fetch('log_filter_nomessages')?></p>
      <?php else: ?>
        <p><?php echo Localization::fetch('log_nomessages')?></p>
      <?php endif; ?>
    <?php endif; ?>
  <?php else: ?>
    <p><?php echo Localization::fetch('log_nomessages_logged')?></p>
  <?php endif; ?>
</div>
</div></div>
