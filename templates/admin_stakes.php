<?php use QuarterPole\Csrf; use QuarterPole\Race\Calls; use QuarterPole\Race\Entries; use QuarterPole\Race\Stakes;
$months = [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$form = $editing ?? ($adding ? ['id' => '', 'name' => '', 'track_id' => 0, 'race_level' => 'g3', 'surface' => 'dirt', 'distance_f' => 8,
    'age_min' => 3, 'age_max' => null, 'sex_rule' => 'open', 'homebred_only' => 0, 'game_month' => 1, 'game_day' => 1, 'weekday' => 'sat',
    'purse' => 30000, 'active' => 1, 'series_name' => null] : null);
$opt = fn ($v, $cur) => '<option value="' . e($v) . '"' . ((string) $v === (string) $cur ? ' selected' : '') . '>';
?>
<section>
  <p class="muted"><a href="admin.php">Admin panel</a> › Stakes calendar</p>
  <div class="heading-row">
    <h1>Stakes calendar</h1>
    <a class="button" href="admin_stakes.php?add=1">Add a stakes race</a>
  </div>
  <p class="muted">Named stakes run every game year, on the first race day of the chosen kind on or after their date. Other graded stakes fill each stakes day with plain names until you name more here. Races at inactive tracks wait until the track opens.</p>

  <?php if ($form): ?>
    <form method="post" action="admin_stakes.php" class="panel stakes-form">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" value="<?= e($form['id']) ?>">
      <h2><?= $form['id'] === '' ? 'Add a stakes race' : 'Edit ' . e($form['name']) ?></h2>
      <?php if ($form['series_name']): ?><p class="note">Leg <?= (int) $form['series_leg'] ?> of the <?= e($form['series_name']) ?>.</p><?php endif ?>
      <label>Name <input type="text" name="name" maxlength="80" value="<?= e($form['name']) ?>" required></label>
      <div class="form-grid">
        <label>Track
          <select name="track_id"><?php foreach ($trackList as $t): ?><?= $opt($t['id'], $form['track_id']) ?><?= e($t['name']) ?><?= $t['active'] ? '' : ' (not open yet)' ?></option><?php endforeach ?></select>
        </label>
        <label>Surface
          <select name="surface"><?php foreach (['dirt' => 'Dirt', 'turf' => 'Turf', 'steeplechase' => 'Steeplechase'] as $k => $v): ?><?= $opt($k, $form['surface']) ?><?= $v ?></option><?php endforeach ?></select>
        </label>
        <label>Distance (furlongs)
          <input type="number" name="distance_f" min="4.5" max="40" step="0.5" value="<?= e((float) $form['distance_f']) ?>">
        </label>
        <label>Grade
          <select name="race_level"><?php foreach (['g1' => 'Grade 1', 'g2' => 'Grade 2', 'g3' => 'Grade 3'] as $k => $v): ?><?= $opt($k, $form['race_level']) ?><?= $v ?></option><?php endforeach ?></select>
        </label>
        <label>Youngest age <input type="number" name="age_min" min="2" max="10" value="<?= (int) $form['age_min'] ?>"></label>
        <label>Oldest age (blank = and up) <input type="number" name="age_max" min="2" max="20" value="<?= e($form['age_max'] ?? '') ?>"></label>
        <label>Sex
          <select name="sex_rule"><?php foreach (['open' => 'Open', 'fillies_mares' => 'Fillies and mares', 'colts_geldings' => 'Colts and geldings'] as $k => $v): ?><?= $opt($k, $form['sex_rule']) ?><?= $v ?></option><?php endforeach ?></select>
        </label>
        <label>Game month
          <select name="game_month"><?php foreach ($months as $k => $v): ?><?= $opt($k, $form['game_month']) ?><?= $v ?></option><?php endforeach ?></select>
        </label>
        <label>On or after day <input type="number" name="game_day" min="1" max="28" value="<?= (int) $form['game_day'] ?>"></label>
        <label>Race day
          <select name="weekday"><?php foreach (Stakes::WEEKDAYS as $k => $v): ?><?= $opt($k, $form['weekday']) ?><?= $v ?></option><?php endforeach ?></select>
        </label>
        <label>Purse (Cash) <input type="number" name="purse" min="1000" step="1000" value="<?= (int) $form['purse'] ?>"></label>
      </div>
      <label class="check"><input type="checkbox" name="homebred_only" value="1" <?= $form['homebred_only'] ? 'checked' : '' ?>> Homebreds only</label>
      <label class="check"><input type="checkbox" name="active" value="1" <?= $form['active'] ? 'checked' : '' ?>> On the calendar (untick to pause it)</label>
      <button type="submit">Save</button> <a href="admin_stakes.php">Cancel</a>
    </form>
  <?php endif ?>

  <div class="table-wrap">
    <table class="list wrap">
      <thead><tr><th>When (game)</th><th>Race</th><th>Grade</th><th>Track</th><th>Distance</th><th>Conditions</th><th class="num">Purse</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($stakes as $st): ?>
        <tr class="<?= $st['active'] && $st['track_active'] ? '' : 'muted' ?>">
          <td><?= e(Stakes::WEEKDAYS[$st['weekday']] === 'Sunday (steeplechase)' ? 'Sunday' : Stakes::WEEKDAYS[$st['weekday']]) ?> on/after <?= e(substr($months[(int) $st['game_month']], 0, 3)) ?> <?= (int) $st['game_day'] ?></td>
          <td><strong><?= e($st['name']) ?></strong><?php if ($st['series_name']): ?><br><span class="muted"><?= e($st['series_name']) ?>, leg <?= (int) $st['series_leg'] ?></span><?php endif ?></td>
          <td><?= e(strtoupper($st['race_level'])) ?></td>
          <td><?= e($st['track_name']) ?></td>
          <td><?= e(Calls::distanceLabel((float) $st['distance_f'])) ?> <?= e($st['surface']) ?></td>
          <td><?= e(Entries::conditions($st)) ?><?= $st['homebred_only'] ? ', homebreds' : '' ?><?= $st['active'] ? '' : ' (paused)' ?></td>
          <td class="num"><?= e(number_format((int) $st['purse'])) ?></td>
          <td><a href="admin_stakes.php?edit=<?= (int) $st['id'] ?>">Edit</a></td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
  </div>
</section>
