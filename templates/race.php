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
    return (int) $c['position'] . ($c['position'] > 1 ? ' <span class="muted">(' . e(Calls::margin($lengths)) . ')</span>' : '');
};
$horseLink = fn (array $e) => $e['origin'] === 'ghost' ? e($e['name']) . ' <span class="muted">(ghost)</span>'
    : '<a href="?page=horse&amp;id=' . (int) $e['horse_id'] . '">' . e($e['name']) . '</a>';
$post = function (string $action, array $fields, string $label) use ($race): string {
    $html = '<form method="post" action="?page=' . $action . '" class="inline">' . Csrf::field()
        . '<input type="hidden" name="race_id" value="' . (int) $race['id'] . '">';
    foreach ($fields as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $html . '<button type="submit" class="small">' . e($label) . '</button></form>';
};
?>
<section>
  <p class="muted"><a href="?page=races&amp;day=<?= e($race['run_on']) ?>">All races that day</a></p>
  <h1><?= e($race['track_name']) ?>, <?= e($race['name']) ?></h1>
  <p class="lede">
    <?= e(Entries::raceLabel($race)) ?> · <?= e(Calls::distanceLabel((float) $race['distance_f'])) ?> on the <?= e($race['surface']) ?> ·
    <?= e(Entries::conditions($race)) ?>
  </p>
  <p class="muted">
    <?= e(GameClock::label(new DateTimeImmutable($race['game_date']))) ?> (real date <?= e($race['run_on']) ?>) ·
    Purse <?= e(money($race['purse'])) ?> · Entry fee <?= e(money($fee)) ?>
    <?php if ($race['track_condition']): ?> · Track <?= e($race['track_condition']) ?><?php endif ?>
  </p>

  <?php if ($race['status'] === 'cancelled'): ?>
    <p class="panel">This race was cancelled because no player horses were entered.</p>
  <?php endif ?>

  <?php if ($run): ?>
    <div class="table-wrap">
      <table class="list chart">
        <thead><tr><th>Horse</th><th>Owner</th><th class="num">PP</th>
          <?php foreach ($callNames as $n): ?><th><?= e($n) ?></th><?php endforeach ?>
          <th>Time</th><th class="num">Earned</th></tr></thead>
        <tbody>
        <?php foreach ($field as $e): ?>
          <tr>
            <td><?= $horseLink($e) ?></td>
            <td><?= e($e['owner_name'] ?? '') ?><?php if ($e['claimer_name']): ?><br><span class="muted">Claimed by <?= e($e['claimer_name']) ?></span><?php endif ?></td>
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
      <p class="muted">Fractions:
        <?= implode(' · ', array_map(fn ($f) => e(Calls::distanceLabel((float) $f['at_f'])) . ' ' . e($time((float) $f['time_s'])), $fractions)) ?>
      </p>
    <?php endif ?>
    <p class="note">Positions at each call, with lengths behind the leader in brackets. Ghost purses show here but are never paid.</p>

  <?php elseif ($race['status'] !== 'cancelled'): ?>
    <div class="panel">
      <h2>Entries (<?= count($field) ?>)</h2>
      <?php if (!$field): ?>
        <p class="muted">No horses entered yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table class="list">
            <thead><tr><th>Horse</th><th>Owner</th><th>Jockey</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($field as $e): ?>
              <tr>
                <td><?= $horseLink($e) ?></td>
                <td><?= e($e['owner_name'] ?? '') ?></td>
                <td><?= e($e['jockey_name']) ?></td>
                <td>
                  <?php if ($isOpen && $member && (int) $e['owner_id'] === $member->id): ?>
                    <?= $post('race-scratch', ['entry_id' => $e['id']], 'Scratch') ?>
                  <?php elseif ($isOpen && $stable && $race['race_level'] === 'claiming' && $e['owner_id'] !== null): ?>
                    <?php if (in_array($e['id'], $myClaims)): ?>
                      <span class="muted">Your claim is in</span>
                    <?php else: ?>
                      <?= $post('race-claim', ['entry_id' => $e['id']], 'Claim for ' . number_format((int) $race['claiming_price'])) ?>
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
      <div class="panel">
        <h2>Enter a horse</h2>
        <?php $ok = array_filter($candidates, fn ($h) => $h['why_not'] === null); ?>
        <?php if (!$ok): ?>
          <p class="muted">None of your active horses fit this race.</p>
        <?php else: ?>
          <form method="post" action="?page=race-enter">
            <?= Csrf::field() ?>
            <input type="hidden" name="race_id" value="<?= (int) $race['id'] ?>">
            <label>Horse
              <select name="horse_id">
                <?php foreach ($ok as $h): ?>
                  <option value="<?= (int) $h['id'] ?>"><?= e($h['name']) ?> (<?= e($h['surface_pref']) ?>, energy <?= (int) $h['energy'] ?>%)</option>
                <?php endforeach ?>
              </select>
            </label>
            <button type="submit">Enter for <?= e(money($fee)) ?></button>
          </form>
        <?php endif ?>
        <?php if ($race['race_level'] === 'claiming'): ?>
          <p class="note">In a claiming race, any other stable can claim your horse for <?= e(money($race['claiming_price'])) ?>. You keep any purse it wins.</p>
        <?php endif ?>
        <?php $not = array_filter($candidates, fn ($h) => $h['why_not'] !== null); if ($not): ?>
          <p class="note">Can't enter: <?= implode(', ', array_map(fn ($h) => e($h['name']) . ' (' . e($h['why_not']) . ')', $not)) ?></p>
        <?php endif ?>
      </div>
    <?php endif ?>
  <?php endif ?>
</section>
