<?php use QuarterPole\Csrf; ?>
<section>
  <p class="muted">Game settings · <a href="?page=admin-reports">Trainer's report wording</a> · <a href="?page=admin-stakes">Stakes calendar</a> · <a href="?page=admin-jockeys">Jockeys</a></p>
  <h1>Game settings</h1>
  <p class="muted">Every tunable number in the game. Changes take effect on the next page load or cron run.</p>
  <form method="post" action="?page=admin-settings">
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
