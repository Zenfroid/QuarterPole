<?php use QuarterPole\Csrf; use QuarterPole\GameClock; use QuarterPole\Horse; use QuarterPole\Training; $realToday = date('Y-m-d'); ?>
<section>
  <div class="heading-row">
    <h1><?= e($stable['stable_name']) ?></h1>
    <a class="button" href="create_horse.php">Create a foundation horse</a>
  </div>
  <?php if ($badges): ?>
    <p class="badges"><?php foreach ($badges as $b): ?><span class="badge"><?= e($b['name'] ?? ucwords(str_replace('_', ' ', $b['badge_code']))) ?></span><?php endforeach ?></p>
  <?php endif ?>

  <?php if (!$list['rows']): ?>
    <p class="panel">Your stable is empty. Use a creation credit to bring in your first foundation horse.</p>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($list['rows'] as $h): $age = GameClock::ageOn($h['birth_game_date'], $today); ?>
        <div class="card">
          <a href="horse.php?id=<?= (int) $h['id'] ?>"><strong><?= e($h['name']) ?></strong></a>
          <span><?= $age ?>yo <?= e(Horse::sexLabel($h, $age)) ?></span>
          <span><?= e($h['color_name']) ?></span>
          <span class="muted"><?= (int) $h['starts'] ?> starts, <?= (int) $h['wins'] ?> wins<?= $h['status'] === 'retired' ? ' · Retired' : '' ?></span>
          <?php if (Training::canTrain($h, $realToday)): ?>
            <form method="post" action="train.php" class="card-action">
              <?= Csrf::field() ?>
              <input type="hidden" name="horse_id" value="<?= (int) $h['id'] ?>">
              <input type="hidden" name="back" value="stable">
              <input type="hidden" name="p" value="<?= (int) $p ?>">
              <button type="submit" class="small">Train</button>
            </form>
          <?php elseif ($h['status'] === 'active'): ?>
            <span class="muted card-action">Trained today</span>
          <?php endif ?>
        </div>
      <?php endforeach ?>
    </div>
    <?php if ($list['pages'] > 1): ?>
      <nav class="pager">
        <?php if ($p > 1): ?><a href="dashboard.php?p=<?= $p - 1 ?>">&larr; Previous</a><?php endif ?>
        <span>Page <?= $p ?> of <?= $list['pages'] ?></span>
        <?php if ($p < $list['pages']): ?><a href="dashboard.php?p=<?= $p + 1 ?>">Next &rarr;</a><?php endif ?>
      </nav>
    <?php endif ?>
  <?php endif ?>
</section>
