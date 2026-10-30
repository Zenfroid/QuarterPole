<?php use QuarterPole\GameClock; use QuarterPole\Horse; ?>
<section>
  <div class="heading-row">
    <h1><?= e($stable['stable_name']) ?></h1>
    <a class="button" href="?page=create-horse">Create a foundation horse</a>
  </div>

  <?php if (!$list['rows']): ?>
    <p class="panel">Your stable is empty. Use a creation credit to bring in your first foundation horse.</p>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($list['rows'] as $h): $age = GameClock::ageOn($h['birth_game_date'], $today); ?>
        <a class="card" href="?page=horse&amp;id=<?= (int) $h['id'] ?>">
          <strong><?= e($h['name']) ?></strong>
          <span><?= $age ?>yo <?= e(Horse::sexLabel($h, $age)) ?></span>
          <span><?= e($h['color_name']) ?></span>
          <span class="muted"><?= (int) $h['starts'] ?> starts, <?= (int) $h['wins'] ?> wins<?= $h['status'] === 'retired' ? ' · Retired' : '' ?></span>
        </a>
      <?php endforeach ?>
    </div>
    <?php if ($list['pages'] > 1): ?>
      <nav class="pager">
        <?php if ($p > 1): ?><a href="?p=<?= $p - 1 ?>">&larr; Previous</a><?php endif ?>
        <span>Page <?= $p ?> of <?= $list['pages'] ?></span>
        <?php if ($p < $list['pages']): ?><a href="?p=<?= $p + 1 ?>">Next &rarr;</a><?php endif ?>
      </nav>
    <?php endif ?>
  <?php endif ?>
</section>
