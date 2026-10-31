<?php use QuarterPole\GameClock; use QuarterPole\Horse; ?>
<section>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="mb-0"><?= e($stable['stable_name']) ?></h1>
      <p class="text-body-secondary mb-0"><?= e(money($stable['cash'])) ?> · <?= (int) $stable['creation_credits'] ?> creation credits</p>
    </div>
    <a class="btn btn-primary" href="stallions.php"><i class="bi bi-stars"></i> Stallions at stud</a>
  </div>

  <ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-flag"></i> Racing (<?= (int) $racingCount ?>)</a></li>
    <li class="nav-item"><a class="nav-link active" href="breeding.php"><i class="bi bi-heart"></i> Breeding barn (<?= (int) $list['total'] ?>)</a></li>
  </ul>

  <?php if (!$list['rows']): ?>
    <div class="card card-body mb-3 empty">
      <i class="bi bi-heart"></i>
      <p class="mb-0">No breeding stock yet. Open a horse's page and use <strong>Retire to breeding</strong> when its racing days are done.</p>
    </div>
  <?php else: ?>
    <?php $groups = ['M' => 'Stallions', 'F' => 'Mares'];
    foreach ($groups as $sex => $title):
        $horses = array_filter($list['rows'], fn ($h) => $h['sex'] === $sex);
        if (!$horses) { continue; } ?>
      <h2 class="h4 mt-2"><?= $title ?></h2>
      <div class="cards mb-4">
        <?php foreach ($horses as $h): $age = GameClock::ageOn($h['birth_game_date'], $today); ?>
          <div class="card card-body horse-card surface-edge-<?= e($h['surface_pref']) ?>">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <a href="horse.php?id=<?= (int) $h['id'] ?>" class="horse-name"><?= e($h['name']) ?></a>
              <?= surface_badge($h['surface_pref']) ?>
            </div>
            <span><?= $age ?>yo <?= e(Horse::sexLabel($h, $age)) ?></span>
            <span><?= e($h['color_name']) ?></span>
            <span class="record" title="(Starts) Wins-Seconds-Thirds, earnings">(<?= (int) $h['starts'] ?>) <?= (int) $h['wins'] ?>-<?= (int) $h['places'] ?>-<?= (int) $h['shows'] ?> $<?= e(number_format((int) $h['earnings'])) ?></span>
            <span class="text-body-secondary"><?= (int) $h['foals'] ?> <?= (int) $h['foals'] === 1 ? 'foal' : 'foals' ?></span>
            <?php if ($sex === 'M'): ?>
              <span class="text-body-secondary"><?= $h['stud_fee'] !== null ? 'At stud for ' . e(money($h['stud_fee'])) : 'Not standing at stud' ?></span>
              <div class="card-action"><a class="btn btn-sm btn-outline-primary" href="breed.php?sire=<?= (int) $h['id'] ?>">Book a mare</a></div>
            <?php else: ?>
              <div class="card-action"><a class="btn btn-sm btn-outline-primary" href="stallions.php">Find a stallion</a></div>
            <?php endif ?>
          </div>
        <?php endforeach ?>
      </div>
    <?php endforeach ?>
    <?php if ($list['pages'] > 1): ?>
      <nav class="d-flex gap-3 justify-content-center align-items-center mt-3">
        <?php if ($p > 1): ?><a href="breeding.php?p=<?= $p - 1 ?>">&larr; Previous</a><?php endif ?>
        <span>Page <?= $p ?> of <?= $list['pages'] ?></span>
        <?php if ($p < $list['pages']): ?><a href="breeding.php?p=<?= $p + 1 ?>">Next &rarr;</a><?php endif ?>
      </nav>
    <?php endif ?>
  <?php endif ?>
</section>
