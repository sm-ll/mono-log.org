<div id="subnav">
  <ul>
    <li><a href="<?php echo $app->urlFor("pages"); ?>" class="active"><?php echo Localization::fetch('pages') ?></a></li>
    <li class="separator">&nbsp;</li>
    <?php foreach($listings as $listing): ?>
      <?php if (CP_Helper::is_page_visible($listing)): ?>
        <li><a href="entries?path=<?php echo $listing['slug']?>"><?php echo $listing['title'] ?></a></li>
      <?php endif ?>
    <?php endforeach ?>
  </ul>
</div>

<div class="container">

  <div id="status-bar">
    <div class="status-block">
      <span class="muted"><?php echo Localization::fetch('viewing_all') ?> </span>
      <span class="folder"><?php echo Localization::fetch('site_pages') ?></span>
    </div>
    <?php if (Config::get('_enable_add_top_level_page', true)): ?>
    <ul>
      <li>
        <?php if ($are_fieldsets):?>
          <?php if (count($fieldsets) > 1): ?>
            <a href="#" class="add-page-btn add-page-modal-trigger" data-path="/" data-title="None">
          <?php else: ?>
            <a href="<?php print $app->urlFor('publish')?>?path=/&new=true&fieldset=<?php print key($fieldsets) ?>&type=none" class="add-page-btn">
          <?php endif ?>
          <span class="ss-icon">add</span>
          <?php echo Localization::fetch('new_top_level_page')?>
        </a>
        <?php endif ?>
      </li>
    </ul>
    <?php endif ?>
  </div>

    <?php $fieldset = 'page' ?>

    <ul id="page-tree">
      <?php foreach ($pages as $page): ?>

      <?php if (CP_Helper::is_page_visible($page)): ?>

      <li class="page" data-url="<?php echo $page['url'] ?>">
        <?php if (array_get($page, 'has_entries', false)): ?> <div class="has-entries"></div><?php endif ?>
        <div class="page-wrapper">
          <?php if (isset($page['children']) && (sizeof($page['children'])> 0)): ?>
            <button class="toggle-children">
              <span class="ss-icon">downright</span>
            </button>
          <?php endif; ?>
          <div class="page-primary">
          <?php
          $base = $page['slug'];
          if ($page['type'] == 'file'): ?>
            <a href="<?php print $app->urlFor('publish')."?path={$page['slug']}"; ?>"><span class="page-title"><?php print (isset($page['title']) && $page['title'] <> '') ? $page['title'] : Slug::prettify($page['slug']) ?></span></a>
          <?php elseif ($page['type'] == 'home'): ?>
            <a href="<?php print $app->urlFor('publish')."?path={$page['url']}"; ?>"><span class="page-title"><?php print $page['title'] ?></span></a>
          <?php else:
            $folder = dirname($page['file_path']);
            ?>
            <a href="<?php print $app->urlFor('publish')."?path={$page['file_path']}"; ?>"><span class="page-title"><?php print (isset($page['title']) && $page['title'] <> '') ? $page['title'] : Slug::prettify($page['slug']) ?></span></a>
          <?php endif ?>

          <?php if (array_get($page, 'has_entries', false)): ?>
            <div class="control-entries">
              <span class="ss-icon">textfile</span>
              <span class="muted"><?php echo $page['entries_label'] ?>:</span>
              <a href="<?php print $app->urlFor('entries')."?path={$base}"; ?>">
                <?php echo Localization::fetch('list')?>
              </a>
              <span class="muted"><?php echo Localization::fetch('or') ?></span>
              <a href="<?php print $app->urlFor('publish')."?path={$base}&new=true"; ?>">
                <?php echo Localization::fetch('create')?>
              </a>
            </div>
          <?php endif ?>
          </div>
          <div class="page-extras">

            <?php if ($page['type'] == 'file'): ?>
              <div class="page-view"><a href="<?php print Path::tidy(Config::getSiteRoot() . '/' . $page['url']) ?>" class="tip" title="View Page"><span class="ss-icon">link</span></a></div>
            <?php elseif ($page['type'] == 'home'): ?>
              <div class="page-view"><a href="<?php print Config::getSiteRoot(); ?>" class="tip" title="View Page"><span class="ss-icon">link</span></a></div>
            <?php else:
              $folder = dirname($page['file_path']);
            ?>
              <div class="page-view"><a href="<?php print Path::tidy(Config::getSiteRoot() . '/' . $page['url']) ?>" class="tip" title="View Page"><span class="ss-icon">link</span></a></div>

              <?php if (Config::get('_enable_add_child_page', true) && ! array_get($page, '_admin:no_children', false)): ?>
              <div class="page-add">
                <a href="#" data-path="<?php print $folder; ?>" data-title="<?php print $page['title']?>" class="tip add-page-btn add-page-modal-trigger" title="<?php echo Localization::fetch('new_child_page')?>"><span class="ss-icon">addfile</span></a>
              </div>
              <?php endif ?>

              <?php if (Config::get('_enable_delete_page', true)):?>
                <div class="page-delete">
                  <?php if (array_get($page, '_admin:protected', false)): ?>
                    <a alt="This page is protected" class="tip"><span class="ss-icon protected">lock</span></a>
                  <?php else: ?>
                    <a class="confirm tip" href="<?php print $app->urlFor('delete_page') . '?path=' . $page['raw_url'] . '&type=' . $page['type']?>" title="<?php echo Localization::fetch('delete_page')?>" data-confirm-message="<?php echo Localization::fetch('pagedelete_confirm')?>">
                      <span class="ss-icon">delete</span>
                    </a>
                  <?php endif ?>
                </div>
              <?php endif ?>
            <?php endif ?>

            <div class="slug-preview">
            <?php if ($page['type'] == 'home'): ?>
              /
            <?php else: print isset($page['url']) ? $page['url'] : $base; endif; ?>
          </div>
          </div>
        </div>
        <?php if (isset($page['children']) && (sizeof($page['children'])> 0)): ?>
          <?php display_folder($app, $page['children'], $page['slug']) ?>
        <?php endif ?>
      </li>
      <?php endif ?>
      <?php endforeach ?>
    </ul>
  </div>

  <?php function display_folder($app, $folder, $base="") {  ?>
  <ul class="subpages">
  <?php foreach ($folder as $page):?>
  <?php if (CP_Helper::is_page_visible($page)): ?>
  <li class="page">
    <div class="page-wrapper">
      <div class="page-primary">

      <!-- PAGE TITLE -->
        <?php if ($page['type'] == 'file'): ?>
          <a href="<?php print $app->urlFor('publish')."?path={$base}/{$page['slug']}"; ?>"><span class="page-title"><?php print isset($page['title']) ? $page['title'] : Slug::prettify($page['slug']) ?></span></a>
        <?php else: ?>
          <a href="<?php print $app->urlFor('publish')."?path={$page['file_path']}"; ?>"><span class="page-title"><?php print isset($page['title']) ? $page['title'] : Slug::prettify($page['slug']) ?></span></a>

        <?php endif ?>

      <!-- ENTRIES -->
      <?php if (isset($page['has_entries']) && $page['has_entries']): ?>
        <div class="control-entries">
          <span class="ss-icon">textfile</span>
          <span class="muted"><?php echo $page['entries_label'] ?>:</span>
          <a href="<?php print $app->urlFor('entries')."?path={$base}/{$page['slug']}"; ?>">
            <?php echo Localization::fetch('list')?>
          </a>
          <span class="muted"><?php echo Localization::fetch('or') ?></span>
          <a href="<?php print $app->urlFor('publish')."?path={$base}/{$page['slug']}&new=true"; ?>">
            <?php echo Localization::fetch('create')?>
          </a>
        </div>
      <?php endif ?>
      </div>

      <!-- SLUG & VIEW PAGE LINK -->
      <div class="page-extras">

        <div class="page-view">
          <a href="<?php print Path::tidy(Config::getSiteRoot() . '/' . $page['url'])?>" class="tip" title="View Page">
            <span class="ss-icon">link</span>
          </a>
        </div>

        <?php if ($page['type'] != 'file' && Config::get('_enable_add_child_page', true)): ?>
          <div class="page-add"><a href="#" data-path="<?php print $page['raw_url']?>" data-title="<?php print $page['title']?>" class="tip add-page-btn add-page-modal-trigger" title="<?php echo Localization::fetch('new_child_page')?>"><span class="ss-icon">addfile</span></a></div>
        <?php endif; ?>

        <?php if (Config::get('_enable_delete_page', true)):?>
          <div class="page-delete">
            <?php if (array_get($page, '_admin:protected', false)): ?>
              <a alt="This page is protected" class="tip"><span class="ss-icon protected">lock</span></a>
            <?php else: ?>
              <a class="confirm tip" href="<?php print $app->urlFor('delete_page') . '?path=' . $page['raw_url'] . '&type=' . $page['type']?>" title="<?php echo Localization::fetch('delete_page')?>" data-confirm-message="<?php echo Localization::fetch('pagedelete_confirm')?>">
                <span class="ss-icon">delete</span>
              </a>
            <?php endif ?>
          </div>
        <?php endif ?>

        <div class="slug-preview">
          <?php print isset($page['url']) ? $page['url'] : $base.' /'.$page['slug'] ?>
        </div>

      </div>

    </div>
    <?php if (isset($page['children']) && (sizeof($page['children'])> 0)) {
      display_folder($app, $page['children'], $base."/".$page['slug']);
    } ?>

  </li>
  <?php endif ?>
  <?php endforeach ?>
  </ul>
  <?php } #end function ?>


<div id="modal-placement"></div>

<script type="text/html" id="fieldset-selector">

<?php if ($are_fieldsets):?>

<div class="modal" id="fieldset-modal" tabindex="1">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3><?php echo Localization::fetch('select_new_page_type')?></h3>
  </div>
  <div class="modal-body">
  <ul>
    <?php foreach ($fieldsets as $fieldset => $fieldset_data): ?>
      <li><a href="<?php print $app->urlFor('publish')?>?path=<%= path %>&new=true&fieldset=<?php print $fieldset ?>&type=none"><?php print $fieldset_data['title'] ?></a></li>
    <?php endforeach; ?>
  </ul>
  <div class="modal-footer">
    <?php echo Localization::fetch('parent')?>: <em><%= parent %></em>
  </div>
</div>

<?php endif ?>

</script>

<script type="text/javascript">
  var selector = _.template($("#fieldset-selector").text());
  $(".add-page-modal-trigger").click(function(){
    var html = selector({
      'path': $(this).attr('data-path'),
      'parent': $(this).attr('data-title')
    });
  $("#modal-placement").html(html);
  $('#fieldset-modal').modal();
});
</script>
