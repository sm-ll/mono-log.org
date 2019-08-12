<div class="container">

  <form enctype="multipart/form-data" method="post" action="publish?path=<?php print $path ?>" data-validate="parsley" class="primary-form">

    <div id="status-bar">
      <div class="status-block">
        <span class="muted"><?php echo $status_message ?>:</span>
        <span class="folder"><?php echo $identifier ?></span>
      </div>
      <ul>
        <?php echo Hook::run('control_panel', 'add_to_status_bar', 'cumulative', null, $path); ?>
        <li>
          <?php print Fieldtype::render_fieldtype('status', 'status', array('display' => 'status'), $status, tabindex());?>
        </li>
        <li>
          <a href="#" class="faux-submit">
            <span class="ss-icon">check</span>
            <?php echo Localization::fetch('save_publish')?>
          </a>
        </li>
      </ul>
    </div>

    <div class="section content">
        <?php
        
        print Hook::run('control_panel', 'add_to_publish_form_header', 'cumulative');
        
        ?>

      <?php print Hook::run('control_panel', 'add_to_publish_form', 'cumulative') ?>

      <input type="hidden" name="page[full_slug]" value="<?php print $full_slug; ?>">
      <input type="hidden" name="page[type]" value="<?php print $type ?>" />
      <input type="hidden" name="page[folder]" value="<?php print $folder ?>" />
      <input type="hidden" name="page[original_slug]" value="<?php print $original_slug ?>" />
      <input type="hidden" name="page[original_datestamp]" value="<?php print $original_datestamp ?>" />
      <input type="hidden" name="page[original_timestamp]" value="<?php print $original_timestamp ?>" />
      <input type="hidden" name="page[original_numeric]" value="<?php print $original_numeric ?>" />
      <input type="hidden" name="return" value="<?php print $return ?>" />

      <?php if (isset($new)): ?>
        <input type="hidden" name="page[new]" value="1" />
      <?php endif ?>

      <?php if (isset($fieldset)): ?>
        <?php if (is_array($fieldset)):?>
          <?php foreach($fieldset as $key => $set): ?>
            <input type="hidden" name="page[fieldset][<?php print $key ?>]" value="<?php print $set; ?>" />
          <?php endforeach; ?>
        <?php else: ?>
          <input type="hidden" name="page[fieldset]" value="<?php print $fieldset; ?>" />
        <?php endif; ?>
      <?php endif ?>

        <?php if (isset($errors) && (sizeof($errors) > 0)): ?>
        <div class="panel topo">
          <p><?php echo Localization::fetch('error') ?></p>
          <ul class="errors">
            <?php foreach ($errors as $field => $error): ?>
            <li><span class="field"><?php print $field ?></span> <span class="error"><?php print $error ?></span></li>
            <?php endforeach ?>
          </ul>
        </div>
        <?php endif ?>

        <?php
        // grab default value and instructions for title
        $title_details = array(
          "instructions" => array(
            "above" => null,
            "below" => null
          ),
          "display" => Localization::fetch('title')
        );

        if (isset($fields) && is_array($fields) && isset($fields['title'])) {
          if (isset($fields['title']['instructions'])) {
            if (!is_array($fields['title']['instructions'])) {
              $title_details["instructions"]["above"] = $fields['title']['instructions'];
            } else {
              if (isset($fields['title']['instructions']['above'])) {
                $title_details["instructions"]["above"] = $fields['title']['instructions']['above'];
              }
              if (isset($fields['title']['instructions']['below'])) {
                $title_details["instructions"]["below"] = $fields['title']['instructions']['below'];
              }
            }
          }
            
          if (isset($fields['title']['display'])) {
            $title_details['display'] = $fields['title']['display'];
          }
        }
        ?>

        <div class="input-block input-text required">
            <?php
            if ($title_details['display']) {
                ?>
                <label for="publish-title"><?php echo $title_details['display']; ?></label>
                <?php
            } else {
                ?>
                <label for="publish-title" style="position: absolute; left: -999em; width: 1em; overflow: hidden;"><?php echo Localization::fetch('title') ?></label>
                <?php
            }
            ?>
          <?php
          if ($title_details['instructions']['above']) {
            echo "<small>{$title_details['instructions']['above']}</small>";
          }
          ?>
          <input name="page[yaml][title]" class="text text-large" data-required="true" tabindex="<?php print tabindex(); ?>" placeholder="<?php echo Localization::fetch('enter_title') ?>" id="publish-title" value="<?php print htmlspecialchars($title); ?>" />
          <?php
          if ($title_details['instructions']['below']) {
            echo "<small>{$title_details['instructions']['below']}</small>";
          }
          ?>
        </div>

        <?php if ($slug !== '/'): ?>
        <div class="input-block input-text required<?php if (array_get($fields, 'slug:hide', false) === true):?> hidden<?php endif ?>">
          <label for="publish-slug"><?php echo Localization::fetch('slug') ?></label>
          <input type="text" id="publish-slug" data-required="true" tabindex="<?php print tabindex(); ?>" class="text<?php if (isset($new)): ?> new-slug <?php endif ?>" name="page[meta][slug]" value="<?php print $slug ?>" />
        </div>
        <?php else: ?>
          <input type="hidden" id="publish-slug" tabindex="<?php print tabindex(); ?>" name="page[meta][slug]" value="<?php print $slug ?>" />
        <?php endif ?>

        <?php if ($type == 'date'): ?>
        <div class="input-block input-date date required<?php if (array_get($fields, 'date:hide', false) === true):?> hidden<?php endif ?>" data-value="<?php print date("Y-m-d", $datestamp) ?>">
          <label><?php echo Localization::fetch('publish_date') ?></label>
          <div class="field">
            <span class="ss-icon">calendar</span>
            <input name="page[meta][publish-date]" tabindex="<?php print tabindex(); ?>" type="text" id="publish-date"  value="<?php print date("Y-m-d", $datestamp) ?>" class="datepicker" />
          </div>
        </div>

        <?php if (Config::getEntryTimestamps()) { ?>
        <div class="input-block input-time time required bootstrap-timepicker<?php if (array_get($fields, 'time:hide', false) === true):?> hidden<?php endif ?>" data-date="<?php print date("h:i a", $timestamp) ?>" data-date-format="h:i a">
          <label><?php echo Localization::fetch('publish_time') ?></label>
          <div class="field">
            <span class="ss-icon">clock</span>
            <input name="page[meta][publish-time]" tabindex="<?php print tabindex(); ?>" type="text" id="publish-time" class="timepicker" value="<?php print date("h:i a", $timestamp) ?>" />
          </div>
        </div>
        <?php } ?>

        <?php elseif ($type == 'number'): ?>
        <div class="input-block input-text input-number<?php if (array_get($fields, 'order:hide', false) === true):?> hidden<?php endif ?>" id="publish-order-number">
          <label for="publish-order-number"><?php echo Localization::fetch('order_number') ?></label>
          <input name="page[meta][publish-numeric]" type="text" class="text date input-4char"  tabindex="<?php print tabindex(); ?>" maxlength="4" id="publish-order-number" value="<?php print $numeric; ?>" />
        </div>
        <?php endif ?>

        <?php
        foreach ($fields as $key => $value):

          if ($key === 'title' || $key === 'slug')
            continue;

          // The default fieldtype is Text.
          $fieldtype = array_get($value, 'type', 'text');

          // Value
          $val = "";
          if (isset($$key)) {
            $val = $$key;
          } elseif (isset($value['default'])) {
            $val = $value['default'];
          }

          # Status is a special system fieldtype
          if ($fieldtype == 'status' && isset($status)) {
            $val = $status;
          }

          // If no display label is set, we'll prettify the fieldname itself
          $value['display'] = array_get($value, 'display', Slug::prettify($key));

          // By default all fields are part of the 'yaml' key. They may need to be overridden
          // to set a meta/system field, like Content.
          $input_key = array_get($value, 'input_key', '[yaml]');

          $wrapper_attributes = array();
          $wrapper_classes = array(
             'input-block',
             'input-' . $fieldtype
           );

          if ( array_get($value, 'required', false)  === TRUE) {
            $wrapper_classes[] = 'required';
          }

          if ( array_get($value, 'hide', false)  === TRUE) {
            $wrapper_classes[] = 'hidden';
          }

        ?>

        <div class="<?php echo implode($wrapper_classes, ' ')?>" <?php echo implode($wrapper_attributes, ' ')?>>
          <?php print Fieldtype::render_fieldtype($fieldtype, $key, $value, $val, tabindex(), $input_key);?>
        </div>

      <?php endforeach ?>
    </div>

    <div id="publish-action" class="footer-controls push-down">
      <button type="submit" class="btn" id="publish-submit"><?php echo Localization::fetch('save_publish') ?></button>
      <button type="submit" class="btn" id="publish-continue-submit" name="continue" value="true"><?php echo Localization::fetch('save_publish_continue') ?></button>
    </div>

  </form>
</div>

<?php

function tabindex()
{
  static $count = 1;

  return $count++;
}

?>