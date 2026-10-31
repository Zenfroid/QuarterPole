<?php use QuarterPole\Csrf; use QuarterPole\GameClock; ?>
<section>
  <h1>Proposed breeding</h1>
  <?php if (!$sire): ?>
    <p class="card card-body mb-3">Pick a stallion from the <a href="stallions.php">stallions at stud</a> first.</p>
  <?php else: ?>
    <p class="lead mb-1">Stallion: <a href="horse.php?id=<?= (int) $sire['id'] ?>"><?= e($sire['name']) ?></a>
      · <?= GameClock::ageOn($sire['birth_game_date'], $today) ?>-year-old · <?= e($sire['color_name']) ?> · prefers <?= e($sire['surface_pref']) ?></p>

    <?php if (!$mares): ?>
      <p class="card card-body mb-3">You have no mares ready to breed. A mare must be at least 4 and retired to breeding (open her page and choose Retire to breeding).</p>
    <?php else: ?>
      <form method="get" action="breed.php" class="card card-body mb-3">
        <input type="hidden" name="sire" value="<?= (int) $sire['id'] ?>">
        <label>Your mare
          <select class="form-select" name="dam" onchange="this.form.submit()">
            <option value="">Choose a mare…</option>
            <?php foreach ($mares as $m): ?>
              <option value="<?= (int) $m['id'] ?>" <?= $dam && (int) $dam['id'] === (int) $m['id'] ? 'selected' : '' ?>>
                <?= e($m['name']) ?> (<?= GameClock::ageOn($m['birth_game_date'], $today) ?>yo, <?= e($m['color_name']) ?>)
              </option>
            <?php endforeach ?>
          </select>
        </label>
        <noscript><button type="submit" class="btn btn-primary">Show</button></noscript>
      </form>
    <?php endif ?>

    <?php if ($proposal): ?>
      <div class="card card-body mb-3">
        <h2><?= e($sire['name']) ?> × <?= e($dam['name']) ?></h2>
        <table class="table table-sm w-auto">
          <tr><th>Stud fee</th><td><?= $proposal['same_owner'] ? 'Free (your own stallion)' : e(money($proposal['fee'])) ?></td></tr>
          <tr><th>Inbreeding (COI)</th><td><?= number_format($proposal['coi'] * 100, 2) ?>%</td></tr>
          <tr><th>Dud chance</th><td><?= (int) $proposal['dud_pct'] ?>%</td></tr>
          <tr><th>Lethal white risk</th><td>
            <?php if (!$proposal['both_tested']): ?>
              <span class="text-body-secondary">Unknown. Genetic-test both parents to see it.</span>
            <?php elseif ($proposal['lethal'] > 0): ?>
              <strong class="text-danger"><?= round($proposal['lethal'] * 100) ?>% chance the foal is born dead.</strong> Both parents carry a lethal white gene (Frame Overo, or W5 or W22 dominant white).
            <?php else: ?>
              None
            <?php endif ?>
          </td></tr>
        </table>

        <?php if ($proposal['book_full']): ?>
          <p class="text-danger"><?= e($sire['name']) ?>'s book is full for this year.</p>
        <?php elseif (!$proposal['years']): ?>
          <p class="text-danger"><?= e($dam['name']) ?> has no open years for a foal with this stallion.</p>
        <?php else: ?>
          <form method="post" action="breed.php">
            <?= Csrf::field() ?>
            <input type="hidden" name="sire_id" value="<?= (int) $sire['id'] ?>">
            <input type="hidden" name="dam_id" value="<?= (int) $dam['id'] ?>">
            <label>Foal's birth year
              <select class="form-select" name="foal_year">
                <?php foreach ($proposal['years'] as $y): $foalAge = (int) $today->format('Y') - GameClock::YEAR_OFFSET - $y; ?>
                  <option value="<?= $y ?>">Year <?= $y ?><?= $foalAge > 0 ? " (backdated: foal is {$foalAge} now)" : ' (this year)' ?></option>
                <?php endforeach ?>
              </select>
            </label>
            <label>Foal's name <input class="form-control" type="text" name="foal_name" maxlength="18" required></label>
            <button type="submit" class="btn btn-primary">Breed<?= $proposal['fee'] > 0 ? ' for ' . e(money($proposal['fee'])) : '' ?></button>
          </form>
          <p class="small text-body-secondary">Backdated foals are born in an earlier year, so they can be old enough to race right away. A mare has one foal per year.</p>
        <?php endif ?>
      </div>
    <?php endif ?>
  <?php endif ?>
</section>
