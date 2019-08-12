<div class="container">

  <div id="status-bar">
    <div class="status-block">
      <span class="muted"><?php echo $status_message ?></span>
      <span class="folder"><?php echo $full_name ?></span>
    </div>
    <ul>
      <li>
        <a href="#" class="faux-submit">
          <span class="ss-icon">check</span>
          <?php echo Localization::fetch('save')?>
        </a>
      </li>
    </ul>
  </div>

  <form method="post" action="member?name=<?php print $original_name ?>" data-validate="parsley" class="primary-form" autocomplete="off">

    <input type="hidden" name="member[original_name]" value="<?php print $original_name ?>" />

    <?php if (isset($new)): ?>
      <input type="hidden" name="member[new]" value="1" />
    <?php endif ?>

    <div class="section content">

      <?php if (isset($errors) && (sizeof($errors) > 0)): ?>
      <div class="panel topo">
        <p><?php echo Localization::fetch('error_form_submission')?></p>
        <ul class="errors">
          <?php foreach ($errors as $field => $error): ?>
          <li><span class="field"><?php print $field ?></span> <span class="error"><?php print $error ?></span></li>
          <?php endforeach ?>
        </ul>
      </div>
      <?php endif ?>

        <?php
        $_errors = (!isset($_errors) || !is_array($_errors)) ? array() : $_errors;
        foreach ($fields['fields'] as $key => $value):

          $fieldtype = array_get($value, 'type', 'text');
          $error = array_get($_errors, $key, null);

          // Value
          $val = "";
          if (isset($$key)) {
            $val = $$key;
          } elseif (isset($value['default'])) {
            $val = $value['default'];
          }

          // By default all fields are part of the 'yaml' key. They may need to be overridden
          // to set a meta/system field, like Content.
          $input_key = array_get($value, 'input_key', '[yaml]');

          $wrapper_attributes = array();
          $wrapper_classes = array(
            'input-block',
            'input-' . $fieldtype
          );

          if (array_get($value, 'required', false) === TRUE) {
            $wrapper_classes[] = 'required';
            $wrapper_attributes[] = 'required';
          }

          if ($fieldtype === 'password') {
            $wrapper_classes[] = 'input-text';
            $wrapper_attributes[] = "data-bind='visible: showChangePassword, css: {required: showChangePassword}'";
          }

          if ($fieldtype === 'show_password') {
            $wrapper_attributes[] = "data-bind='visible: showChangePassword() !== true'";
            if ( ! array_get($value, 'display')) {
              $value['display'] = Localization::fetch('password');
            }
          }

          // If no display label is set, we'll prettify the fieldname itself
          $value['display'] = array_get($value, 'display', Slug::prettify($key));

        ?>

        <div class="<?php echo implode($wrapper_classes, ' ')?>" <?php echo implode($wrapper_attributes, ' ')?>>
          <?php 
          print Fieldtype::render_fieldtype($fieldtype, $key, $value, $val, tabindex(), $input_key, null, $error);
          ?>
        </div>

      <?php endforeach ?>

    </div>

    <div id="publish-action" class="footer-controls push-down">
      <input type="submit" class="btn" value="<?php echo Localization::fetch('save')?>" id="publish-submit">
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

<script type="text/javascript">
  var viewModel = {
      showChangePassword: ko.observable(<?php if (isset($_GET['new'])) {echo "true";} else {echo "false";} ?>),
      changePassword: function() {
        this.showChangePassword(true);
      }
  };
  ko.applyBindings(viewModel);
</script>