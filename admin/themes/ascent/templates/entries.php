<div id="subnav">
  <ul>
    <li><a href="<?php echo $app->urlFor("pages"); ?>"><?php echo Localization::fetch('pages') ?></a></li>
    <li class="separator">&nbsp;</li>
    <?php foreach($listings as $listing): ?>
      <?php if (CP_Helper::is_page_visible($listing)): ?>
        <li><a href="entries?path=<?php echo $listing['slug']?>" <?php if ($listing['slug'] === $path): ?> class="active" <?php endif ?>><?php echo $listing['title'] ?></a></li>
      <?php endif ?>
    <?php endforeach ?>
  </ul>
</div>

<div class="container">

  <div id="status-bar">
    <div class="status-block">
      <span class="muted"><?php echo Localization::fetch('viewing_all')?> <?php echo Localization::fetch('entries', null, true)?> <?php echo Localization::fetch('in')?></span>
      <span class="folder"><?php print $folder; ?></span>
    </div>
    <ul>
      <li>
        <a href="<?php echo $app->urlFor('publish')."?path={$path}&new=true"; ?>">
          <span class="ss-icon">add</span>
          <?php echo Localization::fetch('new_entry')?>
        </a>
      </li>
    </ul>
  </div>

  <form action="<?php print $app->urlFor('delete_entry')?>" action="POST">
    <div class="section">
      <table class="simple-table <?php echo ($type == 'date') ? "entries-" : ''; ?>sortable">
        <thead>
          <tr>
            <th class="checkbox-col"></th>
            <th><div class="header-inner"><?php echo Localization::fetch('title')?></div></th>
            <?php if ($type == 'date'): ?>
              <th><div class="header-inner"><?php echo Localization::fetch('date')?></div></th>
            <?php elseif ($type == 'number' || $type == 'numeric'): ?>
              <th><div class="header-inner"><?php echo Localization::fetch('number')?></div></th>
            <?php endif; ?>
            <th style="width:80px"><div class="header-inner"><?php echo Localization::fetch('status')?></div></th>
            <th style="width:40px"><div class="header-inner"><?php echo Localization::fetch('view')?></div></th>
          </tr>
        </thead>
        <tbody>

        <?php foreach ($entries as $slug => $entry): ?>
        <?php $status = isset($entry['status']) ? $entry['status'] : 'live'; ?>
          <tr>
            <td class="checkbox-col">
            <?php if (array_get($entry, '_admin:protected', false)): ?>
              <span class="ss-icon protected">lock</span>
            <?php else: ?>
              <input type="checkbox" name="entries[]" value="<?php echo "{$path}/{$slug}" ?>" data-bind="checked: selectedEntries" >
            <?php endif ?>
            </td>

            <td class="title">
              <a href="<?php print $app->urlFor('publish')?>?path=<?php echo Path::tidy($path.'/')?><?php echo $slug ?>"><?php print (isset($entry['title']) && $entry['title'] <> '') ? $entry['title'] : Slug::prettify($entry['slug']) ?></a>
            </td>

            <?php if ($type == 'date'): ?>
              <td data-fulldate="<?php echo $entry['datestamp']; ?>">
                  <?php
                  echo Date::format(Config::getDateFormat('Y/m/d'), $entry['datestamp']);
                  ?>
              </td>
            <?php elseif ($type == 'number'): ?>
              <td><?php print $entry['numeric'] ?></td>
            <?php endif ?>
            <td class="margin status status-<?php print $status ?>">
              <span class="ss-icon">record</span><?php print ucwords($status) ?>
            </td>
            <td class="center">
              <a href="<?php echo $entry['url']?>" class="entry-view" target="_blank"><span class="ss-icon">link</span></a>
            </td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <div class="take-action clearfix">
      <div class="input-status block-action pull-left" data-bind="css: {disabled: selectedEntries().length < 1}">
        <div class="input-select-wrap">
          <select data-bind="enable: selectedEntries().length > 0, selectedOptions: selectedAction">
            <option value=""><?php echo Localization::fetch('take_action')?></option>
            <option value="delete"><?php echo Localization::fetch('delete_entries')?></option>
          </select>
        </div>
      </div>

       <input type="submit" class="btn pull-left" data-bind="visible: selectedAction() != '' && selectedEntries().length > 0" value="<?php echo Localization::fetch('confirm_action')?>">
    </div>
  </form>

  </div>
</div>


<script type="text/javascript">
  var viewModel = {
      selectedEntries: ko.observableArray(),
      selectedAction: ko.observable(''),
  };

  viewModel.selectedEntries.subscribe(function(item){
    // console.log('selected ' + item);
  }, viewModel);

  viewModel.selectedAction.subscribe(function(action) {
    // console.log('selected ' + action);
  }, viewModel);

  ko.applyBindings(viewModel);

</script>
