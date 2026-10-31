<?php use QuarterPole\Csrf; use QuarterPole\GameClock; use QuarterPole\Race\Calls; use QuarterPole\Race\Entries;
$at = Calls::forDistance((float) $race['distance_f']);
$callNames = ['start' => 'Start', 'first' => Calls::distanceLabel($at['first']), 'second' => $at['second'] ? Calls::distanceLabel($at['second']) : null, 'stretch' => 'Stretch', 'finish' => 'Finish'];
$callNames = array_filter($callNames);
$run = $race['status'] === 'run';
$time = fn (float $s) => sprintf('%d:%05.2f', intdiv((int) $s, 60), fmod($s, 60));
$cell = function (?array $c) {
    if (!$c) {
        return '';
    }
    $lengths = (float) $c['lengths_behind'];
    return (int) $c['position'] . ($c['position'] > 1 ? ' <span class="text-body-secondary">(' . e(Calls::margin($lengths)) . ')</span>' : '');
};
$horseLink = fn (array $e) => $e['origin'] === 'ghost' ? e($e['name']) . ' <span class="text-body-secondary">(ghost)</span>'
    : '<a href="horse.php?id=' . (int) $e['horse_id'] . '">' . e($e['name']) . '</a>';
$post = function (string $action, array $fields, string $label) use ($race): string {
    $html = '<form method="post" action="race_action.php?do=' . $action . '" class="d-inline">' . Csrf::field()
        . '<input type="hidden" name="race_id" value="' . (int) $race['id'] . '">';
    foreach ($fields as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $html . '<button type="submit" class="btn btn-sm btn-outline-primary">' . e($label) . '</button></form>';
};
?>
<section>
  <p class="text-body-secondary"><a href="races.php?day=<?= e($race['run_on']) ?>">All races that day</a></p>
  <h1><?= e($race['track_name']) ?>, <?= e($race['name']) ?></h1>
  <p class="lead mb-1">
    <?= e(Entries::raceLabel($race)) ?> · <?= e(Calls::distanceLabel((float) $race['distance_f'])) ?> on the <?= e($race['surface']) ?> ·
    <?= e(Entries::conditions($race)) ?>
  </p>
  <p class="text-body-secondary">
    <?= e(GameClock::label(new DateTimeImmutable($race['game_date']))) ?> (real date <?= e($race['run_on']) ?>) ·
    Purse <?= e(money($race['purse'])) ?> · Entry fee <?= e(money($fee)) ?>
    <?php if ($going): ?> · Track: <strong><?= e(ucfirst($going)) ?></strong><?php endif ?>
  </p>

  <?php if ($member && $member->isAdmin && in_array($race['race_level'], ['g1', 'g2', 'g3'], true) && $race['stakes_id'] === null): ?>
    <form method="post" action="admin_race.php" class="card card-body mb-3 border-start border-4 border-primary">
      <?= Csrf::field() ?>
      <input type="hidden" name="race_id" value="<?= (int) $race['id'] ?>">
      <h2 class="h5">Name this stakes <span class="badge text-bg-secondary">Admin</span></h2>
      <label>Name <input class="form-control" type="text" name="name" maxlength="80" value="<?= e($race['name']) ?>" required></label>
      <label class="check"><input class="form-check-input" type="checkbox" name="yearly" value="1" checked> Run it every year under this name (adds it to the stakes calendar)</label>
      <div><button type="submit" class="btn btn-primary btn-sm">Save name</button></div>
    </form>
  <?php elseif ($member && $member->isAdmin && $race['stakes_id'] !== null): ?>
    <p class="small text-body-secondary">Admin: rename this race on the <a href="admin_stakes.php?edit=<?= (int) $race['stakes_id'] ?>">stakes calendar</a>.</p>
  <?php endif ?>

  <?php if ($race['status'] === 'cancelled'): ?>
    <p class="card card-body mb-3">This race was cancelled because no player horses were entered.</p>
  <?php endif ?>

  <?php if ($run): ?>
    <div class="table-wrap">
      <table class="table table-hover align-middle chart">
        <thead><tr><th>Horse</th><th>Owner</th><th class="num">PP</th>
          <?php foreach ($callNames as $n): ?><th><?= e($n) ?></th><?php endforeach ?>
          <th>Time</th><th class="num">Earned</th></tr></thead>
        <tbody>
        <?php foreach ($field as $e): ?>
          <tr>
            <td><?= $horseLink($e) ?></td>
            <td><?= e($e['owner_name'] ?? '') ?><?php if ($e['claimer_name']): ?><br><span class="text-body-secondary">Claimed by <?= e($e['claimer_name']) ?></span><?php endif ?></td>
            <td class="num"><?= (int) $e['post_position'] ?></td>
            <?php foreach (array_keys($callNames) as $k): ?><td><?= $cell($calls[$e['id']][$k] ?? null) ?></td><?php endforeach ?>
            <td class="num"><?= $e['final_time'] !== null ? e($time((float) $e['final_time'])) : '' ?></td>
            <td class="num"><?= e(number_format((int) $e['earnings'])) ?></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php if ($fractions): ?>
      <p class="text-body-secondary">Fractions:
        <?= implode(' · ', array_map(fn ($f) => e(Calls::distanceLabel((float) $f['at_f'])) . ' ' . e($time((float) $f['time_s'])), $fractions)) ?>
      </p>
    <?php endif ?>
    <p class="small text-body-secondary">Positions at each call, with lengths behind the leader in brackets. Ghost purses show here but are never paid.</p>

  <?php elseif ($race['status'] !== 'cancelled'): ?>
    <div class="card card-body mb-3">
      <h2>Entries (<?= count($field) ?>)</h2>
      <?php if (!$field): ?>
        <p class="text-body-secondary">No horses entered yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table table-hover align-middle">
            <thead><tr><th>Horse</th><th>Owner</th><th>Jockey</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($field as $e): ?>
              <tr>
                <td><?= $horseLink($e) ?></td>
                <td><?= e($e['owner_name'] ?? '') ?></td>
                <td><?= e($e['jockey_name']) ?></td>
                <td>
                  <?php if ($isOpen && $member && (int) $e['owner_id'] === $member->id): ?>
                    <?= $post('scratch', ['entry_id' => $e['id']], 'Scratch') ?>
                  <?php elseif ($isOpen && $stable && $race['race_level'] === 'claiming' && $e['owner_id'] !== null): ?>
                    <?php if (in_array($e['id'], $myClaims)): ?>
                      <span class="text-body-secondary">Your claim is in</span>
                    <?php else: ?>
                      <?= $post('claim', ['entry_id' => $e['id']], 'Claim for ' . number_format((int) $race['claiming_price'])) ?>
                    <?php endif ?>
                  <?php endif ?>
                </td>
              </tr>
            <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif ?>
    </div>

    <?php if ($isOpen && $stable): ?>
      <div class="card card-body mb-3">
        <h2>Enter a horse</h2>
        <?php $ok = array_filter($candidates, fn ($h) => $h['why_not'] === null); ?>
        <?php if (!$ok): ?>
          <p class="text-body-secondary">None of your active <?= e($race['surface']) ?> horses fit this race.</p>
        <?php else: ?>
          <form method="post" action="race_action.php?do=enter">
            <?= Csrf::field() ?>
            <input type="hidden" name="race_id" value="<?= (int) $race['id'] ?>">
            <label>Horse
              <select class="form-select" name="horse_id">
                <?php foreach ($ok as $h): ?>
                  <option value="<?= (int) $h['id'] ?>"><?= e($h['name']) ?> (energy <?= (int) $h['energy'] ?>%)</option>
                <?php endforeach ?>
              </select>
            </label>
            <button type="submit" class="btn btn-primary">Enter for <?= e(money($fee)) ?></button>
          </form>
        <?php endif ?>
        <?php if ($race['race_level'] === 'claiming'): ?>
          <p class="small text-body-secondary">In a claiming race, any other stable can claim your horse for <?= e(money($race['claiming_price'])) ?>. You keep any purse it wins.</p>
        <?php endif ?>
        <?php $not = array_filter($candidates, fn ($h) => $h['why_not'] !== null); if ($not): ?>
          <p class="small text-body-secondary">Can't enter: <?= implode(', ', array_map(fn ($h) => e($h['name']) . ' (' . e($h['why_not']) . ')', $not)) ?></p>
        <?php endif ?>
      </div>
    <?php endif ?>
  <?php endif ?>
</section>
