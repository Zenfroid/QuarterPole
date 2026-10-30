<?php use QuarterPole\Csrf; use QuarterPole\GameClock; use QuarterPole\Horse; use QuarterPole\Maturity; use QuarterPole\Training;
$age = GameClock::ageOn($horse['birth_game_date'], $today);
$maturity = Maturity::factor($horse, $age, Maturity::yearFraction($today), $peakYears);
$labels = ['basic' => 'Basic trainer', 'experienced' => 'Experienced trainer', 'expert' => 'Expert trainer'];
$isStallion = $horse['sex'] === 'M' && !$horse['gelded'];
$link = fn (?array $h) => $h ? '<a href="?page=horse&amp;id=' . (int) $h['id'] . '">' . e($h['name']) . '</a>' : '<span class="muted">Foundation</span>';
$action = function (string $name, string $label) use ($horse): string {
    return '<form method="post" action="?page=horse-action" class="inline">' . Csrf::field()
        . '<input type="hidden" name="horse_id" value="' . (int) $horse['id'] . '">'
        . '<input type="hidden" name="action" value="' . e($name) . '">'
        . '<button type="submit">' . e($label) . '</button></form>';
};
?>
<section>
  <h1><?= e($horse['name']) ?></h1>
  <p class="lede">
    <?= $age ?>-year-old <?= e(strtolower(Horse::sexLabel($horse, $age))) ?> ·
    <?= e($horse['color_name']) ?> ·
    <?= e(ucfirst($horse['status'])) ?><?= $horse['status'] === 'retired' ? ' to breeding' : '' ?>
  </p>
  <p class="muted">
    Owner: <?= e($horse['owner_name'] ?? ($horse['origin'] === 'game_stallion' ? 'Quarter Pole Stud' : 'None')) ?> ·
    <?= match ($horse['origin']) { 'foundation' => 'Foundation horse', 'game_stallion' => 'Game stallion', default => 'Born in the game, Year ' . ((int) substr($horse['birth_game_date'], 0, 4) - GameClock::YEAR_OFFSET) } ?>
  </p>

  <?php if ($isStallion && $horse['status'] === 'retired' && $horse['stud_fee'] !== null): ?>
    <div class="panel highlight">
      Standing at stud for <strong><?= e(money($horse['stud_fee'])) ?></strong>.
      <?php if ($stable): ?><a class="button" href="?page=breed&amp;sire=<?= (int) $horse['id'] ?>">Book a mare</a><?php endif ?>
    </div>
  <?php endif ?>

  <div class="grid">
    <div class="panel">
      <h2>Record</h2>
      <p><?= (int) $horse['starts'] ?> starts: <?= (int) $horse['wins'] ?> wins, <?= (int) $horse['places'] ?> seconds, <?= (int) $horse['shows'] ?> thirds</p>
      <p>Earnings: <?= e(money($horse['earnings'])) ?></p>
    </div>

    <div class="panel">
      <h2>Pedigree</h2>
      <table class="pedigree">
        <tr><td rowspan="2"><?= $link($tree['sire'] ?? null) ?></td><td><?= $link($tree['sire']['sire'] ?? null) ?></td></tr>
        <tr><td><?= $link($tree['sire']['dam'] ?? null) ?></td></tr>
        <tr><td rowspan="2"><?= $link($tree['dam'] ?? null) ?></td><td><?= $link($tree['dam']['sire'] ?? null) ?></td></tr>
        <tr><td><?= $link($tree['dam']['dam'] ?? null) ?></td></tr>
      </table>
      <p class="muted">Inbreeding (COI, 5 generations): <?= number_format((float) $horse['coi'] * 100, 2) ?>%</p>
    </div>
  </div>

  <?php if ($horse['genetics_tested']): ?>
    <div class="panel">
      <h2>Genetic test</h2>
      <p class="genes">
        E: <?= e($horse['gene_e']) ?> · A: <?= e($horse['gene_a']) ?> · G: <?= e($horse['gene_g']) ?> · Cr: <?= e($horse['gene_cr']) ?> ·
        O: <?= e($horse['gene_o']) ?> · W5: <?= e($horse['gene_w5']) ?> · W20: <?= e($horse['gene_w20']) ?> · W22: <?= e($horse['gene_w22']) ?>
      </p>
    </div>
  <?php endif ?>

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
      <h2>Owner options</h2>
      <div class="actions">
        <?php if ($horse['status'] === 'active'): ?>
          <?= $action('retire', 'Retire to breeding') ?>
        <?php endif ?>
        <?php if (!$horse['genetics_tested']): ?>
          <?= $action('test', 'Genetic test (' . money($testCost) . ')') ?>
        <?php endif ?>
        <?php if ($horse['sex'] === 'F' && $horse['status'] === 'retired'): ?>
          <a class="button" href="?page=studs">Find a stallion</a>
        <?php endif ?>
        <?php if ($isStallion && $horse['status'] === 'retired'): ?>
          <a class="button" href="?page=breed&amp;sire=<?= (int) $horse['id'] ?>">Breed to one of your mares</a>
        <?php endif ?>
      </div>
      <?php if ($horse['status'] === 'active'): ?>
        <p class="note">Retiring is permanent: a retired horse never races again.</p>
      <?php endif ?>

      <?php if ($isStallion && $horse['status'] === 'retired'): ?>
        <h3>Stud settings</h3>
        <form method="post" action="?page=horse-action">
          <?= Csrf::field() ?>
          <input type="hidden" name="horse_id" value="<?= (int) $horse['id'] ?>">
          <input type="hidden" name="action" value="stud">
          <label>Stud fee (Cash; leave blank to not stand at stud)
            <input type="number" name="stud_fee" min="0" step="100" value="<?= e($horse['stud_fee'] ?? '') ?>">
          </label>
          <label>Book size (mares per game year)
            <input type="number" name="book_size" min="1" max="100" value="<?= (int) ($horse['book_size'] ?? 40) ?>">
          </label>
          <button type="submit">Save stud settings</button>
        </form>
        <p class="note">Your own mares can always use him for free.</p>
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

  <?php if ($offspring): ?>
    <div class="panel">
      <h2>Offspring</h2>
      <ul class="plain">
        <?php foreach ($offspring as $o): ?>
          <li><a href="?page=horse&amp;id=<?= (int) $o['id'] ?>"><?= e($o['name']) ?></a> <span class="muted">born Year <?= (int) substr($o['birth_game_date'], 0, 4) - GameClock::YEAR_OFFSET ?></span></li>
        <?php endforeach ?>
      </ul>
    </div>
  <?php endif ?>
</section>
