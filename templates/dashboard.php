<?php use QuarterPole\Csrf; use QuarterPole\GameClock; use QuarterPole\Horse; use QuarterPole\Training; $realToday = date('Y-m-d'); ?>
<section>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="mb-0"><?= e($stable['stable_name']) ?></h1>
      <p class="text-body-secondary mb-0"><?= e(money($stable['cash'])) ?> · <?= (int) $stable['creation_credits'] ?> creation credits</p>
    </div>
    <a class="btn btn-primary" href="create_horse.php"><i class="bi bi-plus-lg"></i> Create a foundation horse</a>
  </div>
  <?php if ($badges): ?>
    <p class="badges"><?php foreach ($badges as $b): ?><span class="badge rounded-pill text-bg-success"><?= e($b['name'] ?? ucwords(str_replace('_', ' ', $b['badge_code']))) ?></span><?php endforeach ?></p>
  <?php endif ?>

  <ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-flag"></i> Racing (<?= (int) $list['total'] ?>)</a></li>
    <li class="nav-item"><a class="nav-link" href="breeding.php"><i class="bi bi-heart"></i> Breeding barn (<?= (int) $barnCount ?>)</a></li>
  </ul>

  <?php if (!$list['rows']): ?>
    <div class="card card-body mb-3 empty">
      <i class="bi bi-house-heart"></i>
      <p class="mb-0"><?= $barnCount ? 'No horses in training right now.' : 'Your stable is empty.' ?> Use a creation credit to bring in a foundation horse<?= $barnCount ? ', or breed one in the Breeding barn' : '' ?>.</p>
    </div>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($list['rows'] as $h): $age = GameClock::ageOn($h['birth_game_date'], $today); ?>
        <div class="card card-body horse-card surface-edge-<?= e((string) $h['racing_surface']) ?>">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <a href="horse.php?id=<?= (int) $h['id'] ?>" class="horse-name"><?= e($h['name']) ?></a>
            <?= surface_badge($h['racing_surface']) ?>
          </div>
          <span><?= $age ?>yo <?= e(Horse::sexLabel($h, $age)) ?></span>
          <span><?= e($h['color_name']) ?></span>
          <span class="record" title="(Starts) Wins-Seconds-Thirds, earnings">(<?= (int) $h['starts'] ?>) <?= (int) $h['wins'] ?>-<?= (int) $h['places'] ?>-<?= (int) $h['shows'] ?> $<?= e(number_format((int) $h['earnings'])) ?></span>
          <?php if ($h['status'] === 'active'): ?>
            <span class="energy-label">Energy</span>
            <div class="progress energy" role="progressbar" aria-label="Energy" aria-valuenow="<?= (int) $h['energy'] ?>" aria-valuemin="0" aria-valuemax="100" title="Energy <?= (int) $h['energy'] ?>%">
              <div class="progress-bar <?= $h['energy'] < 50 ? 'bg-warning' : 'bg-success' ?>" style="width: <?= (int) $h['energy'] ?>%"></div>
            </div>
          <?php endif ?>
          <?php if (Training::canTrain($h, $realToday)): ?>
            <form method="post" action="train.php" class="card-action">
              <?= Csrf::field() ?>
              <input type="hidden" name="horse_id" value="<?= (int) $h['id'] ?>">
              <input type="hidden" name="back" value="stable">
              <input type="hidden" name="p" value="<?= (int) $p ?>">
              <button type="submit" class="btn btn-sm btn-outline-primary">Train</button>
            </form>
          <?php elseif ($h['status'] === 'active'): ?>
            <span class="text-body-secondary card-action small"><i class="bi bi-check2"></i> Trained today</span>
          <?php endif ?>
        </div>
      <?php endforeach ?>
    </div>
    <?php if ($list['pages'] > 1): ?>
      <nav class="d-flex gap-3 justify-content-center align-items-center mt-3">
        <?php if ($p > 1): ?><a href="dashboard.php?p=<?= $p - 1 ?>">&larr; Previous</a><?php endif ?>
        <span>Page <?= $p ?> of <?= $list['pages'] ?></span>
        <?php if ($p < $list['pages']): ?><a href="dashboard.php?p=<?= $p + 1 ?>">Next &rarr;</a><?php endif ?>
      </nav>
    <?php endif ?>
  <?php endif ?>
</section>
