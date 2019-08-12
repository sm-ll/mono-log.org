<div class="container">

  <div id="status-bar">
    <div class="status-block">
      <span class="muted"><?php echo Localization::fetch('viewing_all')?> </span>
      <span class="folder"><?php echo Localization::fetch('members')?></span>
    </div>
    <ul>
      <li>
        <a href="<?php print $app->urlFor('member')."?new=1"; ?>">
          <span class="ss-icon">adduser</span>
          <?php echo Localization::fetch('new_member')?>
        </a>
      </li>
    </ul>
  </div>

  <div class="section">

    <?php foreach ($members as $name => $member): ?>
    <div class="block member-block">
      <img src="<?php echo $member->get_gravatar(42) ?>" class="avatar" />
      <a class="confirm" href="<?php print $app->urlFor('deletemember')."?name={$name}"; ?>"><span class="ss-icon">delete</span></a>
      <a href="<?php echo $app->urlFor("member")."?name={$name}"; ?>" class="member-name"><?php echo $member->get_full_name() ?></a>
      <a href="<?php echo $app->urlFor("member")."?name={$name}"; ?>" class="member-email"><?php echo $member->get_email() ?></a>
    </div>
    <?php endforeach ?>

  </div>

</div>