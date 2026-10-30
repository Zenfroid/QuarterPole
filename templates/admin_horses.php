<?php use QuarterPole\GameClock; use QuarterPole\Horse; ?>
<section>
  <p class="muted"><a href="admin.php">Admin panel</a> › Horses</p>
  <h1>Horses</h1>
  <form method="get" action="admin_horses.php" class="search">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Horse name, stable name or horse number">
    <button type="submit">Search</button>
  </form>
  <p class="muted"><?= $q === '' ? 'The 50 newest horses.' : count($found) . ' found.' ?></p>
  <div class="table-wrap">
    <table class="list">
      <thead><tr><th>#</th><th>Name</th><th>Age and sex</th><th>Surface</th><th>Owner</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($found as $h): $age = GameClock::ageOn($h['birth_game_date'], $today); ?>
        <tr>
          <td class="num"><?= (int) $h['id'] ?></td>
          <td><a href="horse.php?id=<?= (int) $h['id'] ?>"><?= e($h['name']) ?></a></td>
          <td><?= $age ?>yo <?= e(strtolower(Horse::sexLabel($h, $age))) ?></td>
          <td><?= e($h['surface_pref']) ?></td>
          <td><?= e($h['stable_name'] ?? ($h['origin'] === 'game_stallion' ? 'Quarter Pole Stud' : ($h['origin'] === 'ghost' ? 'House horse' : 'None'))) ?></td>
          <td><?= e(ucfirst($h['status'])) ?></td>
          <td><a class="button small" href="admin_horse.php?id=<?= (int) $h['id'] ?>">Edit</a></td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
  </div>
</section>
