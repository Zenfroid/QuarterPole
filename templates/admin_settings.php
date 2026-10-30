<?php use QuarterPole\Csrf; ?>
<section>
  <p class="muted"><a href="admin.php">Admin panel</a> › Game settings</p>
  <h1>Game settings</h1>
  <p class="muted">Every tunable number in the game. Changes take effect on the next page load or cron run.</p>
  <form method="post" action="admin_settings.php">
    <?= Csrf::field() ?>
    <?php foreach ($groups as $group => $rows): ?>
      <div class="panel">
        <h2><?= e($group) ?></h2>
        <div class="settings">
          <?php foreach ($rows as $r): ?>
            <label>
              <code><?= e($r['setting_key']) ?></code>
              <?php if ($r['description']): ?><span class="muted"><?= e($r['description']) ?></span><?php endif ?>
              <input type="text" name="s[<?= e($r['setting_key']) ?>]" value="<?= e($r['setting_value']) ?>">
            </label>
          <?php endforeach ?>
        </div>
      </div>
    <?php endforeach ?>
    <button type="submit">Save changes</button>
  </form>
</section>
