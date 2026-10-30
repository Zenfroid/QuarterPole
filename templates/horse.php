<?php use QuarterPole\Csrf; use QuarterPole\GameClock; use QuarterPole\Horse; use QuarterPole\Maturity; use QuarterPole\Training;
$age = GameClock::ageOn($horse['birth_game_date'], $today);
$maturity = Maturity::factor($horse, $age, Maturity::yearFraction($today), $peakYears);
$labels = ['basic' => 'Basic trainer', 'experienced' => 'Experienced trainer', 'expert' => 'Expert trainer'];
?>
<section>
  <h1><?= e($horse['name']) ?></h1>
  <p class="lede">
    <?= $age ?>-year-old <?= e(strtolower(Horse::sexLabel($horse, $age))) ?> ·
    <?= e($horse['color_name']) ?> ·
    <?= e(ucfirst($horse['status'])) ?>
  </p>
  <p class="muted">
    Owner: <?= e($horse['owner_name'] ?? 'None') ?> ·
    <?= $horse['origin'] === 'foundation' ? 'Foundation horse' : 'Born in the game' ?>
  </p>

  <div class="grid">
    <div class="panel">
      <h2>Record</h2>
      <p><?= (int) $horse['starts'] ?> starts: <?= (int) $horse['wins'] ?> wins, <?= (int) $horse['places'] ?> seconds, <?= (int) $horse['shows'] ?> thirds</p>
      <p>Earnings: <?= e(money($horse['earnings'])) ?></p>
    </div>

    <div class="panel">
      <h2>Pedigree</h2>
      <p>Sire: <?= $parents['sire'] ? '<a href="?page=horse&amp;id=' . (int) $parents['sire']['id'] . '">' . e($parents['sire']['name']) . '</a>' : 'Foundation' ?></p>
      <p>Dam: <?= $parents['dam'] ? '<a href="?page=horse&amp;id=' . (int) $parents['dam']['id'] . '">' . e($parents['dam']['name']) . '</a>' : 'Foundation' ?></p>
      <p class="muted">Inbreeding (COI): <?= number_format((float) $horse['coi'] * 100, 2) ?>%</p>
    </div>
  </div>

  <?php if ($isOwner): ?>
    <div class="panel">
      <h2>Training</h2>
      <p><?= e(Maturity::label($maturity, $age, (int) $horse['peak_age'])) ?></p>
      <?php if (Training::canTrain($horse, date('Y-m-d'))): ?>
        <form method="post" action="?page=train">
          <?= Csrf::field() ?>
          <input type="hidden" name="horse_id" value="<?= (int) $horse['id'] ?>">
          <button type="submit">Train</button>
        </form>
      <?php elseif ($horse['status'] === 'active'): ?>
        <p class="muted">Trained today. Come back tomorrow.</p>
      <?php endif ?>
    </div>

    <div class="panel">
      <h2>Trainer's notes</h2>
      <?php foreach ($reports as $r): ?>
        <h3><?= e($labels[$r['level']]) ?></h3>
        <ul class="notes">
          <?php foreach (explode("\n", $r['notes']) as $line): ?><li><?= e($line) ?></li><?php endforeach ?>
        </ul>
      <?php endforeach ?>
      <?php if (!$reports): ?><p class="muted">Notes arrive when the horse turns 2.</p><?php endif ?>
    </div>
  <?php endif ?>
</section>
