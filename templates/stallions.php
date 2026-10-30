<?php use QuarterPole\GameClock; ?>
<section>
  <div class="heading-row">
    <h1>Stallions at stud</h1>
    <nav class="filters">
      <a href="stallions.php" class="<?= $surface ? '' : 'on' ?>">All</a>
      <?php foreach (['dirt' => 'Dirt', 'turf' => 'Turf', 'steeplechase' => 'Steeplechase'] as $k => $label): ?>
        <a href="stallions.php?surface=<?= $k ?>" class="<?= $surface === $k ? 'on' : '' ?>"><?= $label ?></a>
      <?php endforeach ?>
    </nav>
  </div>
  <?php if (!$studs): ?>
    <p class="panel">No stallions are standing at stud yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="list">
        <thead><tr><th>Stallion</th><th>Age</th><th>Color</th><th>Surface</th><th>Owner</th><th>Fee</th><th>Book</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($studs as $h):
            $cap = $h['origin'] === 'game_stallion' ? $bookCaps['game'] : min($bookCaps['player'], (int) ($h['book_size'] ?? $bookCaps['player']));
            $left = max(0, $cap - (int) $h['booked']); ?>
          <tr>
            <td><a href="horse.php?id=<?= (int) $h['id'] ?>"><?= e($h['name']) ?></a></td>
            <td><?= GameClock::ageOn($h['birth_game_date'], $today) ?></td>
            <td><?= e($h['color_name']) ?></td>
            <td><?= e(ucfirst($h['surface_pref'])) ?></td>
            <td><?= e($h['owner_name'] ?? 'Quarter Pole Stud') ?></td>
            <td class="num"><?= e(money($h['stud_fee'])) ?></td>
            <td class="num"><?= $left ?> of <?= $cap ?> left</td>
            <td><?php if ($left > 0): ?><a href="breed.php?sire=<?= (int) $h['id'] ?>">Book a mare</a><?php else: ?><span class="muted">Full</span><?php endif ?></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
  <p class="note">Your own stallions breed your own mares for free; open his page and use Book a mare.</p>
</section>
