<?php use QuarterPole\GameClock; use QuarterPole\Race\Calls; use QuarterPole\Race\Entries;
$byTrack = [];
foreach ($races as $r) {
    $byTrack[$r['track_name']][] = $r;
}
$status = function (array $r) use ($realToday): string {
    return match (true) {
        $r['status'] === 'run' => 'Results',
        $r['status'] === 'cancelled' => 'Cancelled',
        $r['run_on'] === $realToday => 'Runs tonight',
        default => 'Open',
    };
};
?>
<section>
  <h1>Races</h1>
  <nav class="days">
    <?php foreach ($days as $d): $dt = new DateTimeImmutable($d['run_on']); ?>
      <a href="races.php?day=<?= e($d['run_on']) ?>" class="<?= $d['run_on'] === $day ? 'on' : '' ?>">
        <strong><?= $d['run_on'] === $realToday ? 'Today' : e($dt->format('D j M')) ?></strong>
        <span><?= e(GameClock::label(new DateTimeImmutable($d['game_date']))) ?></span>
      </a>
    <?php endforeach ?>
  </nav>

  <?php if (!$races): ?>
    <p class="panel">No races on this day.</p>
  <?php endif ?>

  <?php foreach ($byTrack as $track => $list): ?>
    <h2><?= e($track) ?></h2>
    <div class="table-wrap">
      <table class="list">
        <thead><tr><th>Race</th><th>Type</th><th>Conditions</th><th>Distance</th><th class="num">Purse</th><th class="num">Entry</th><th class="num">Field</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $r): ?>
          <tr>
            <td><a href="race.php?id=<?= (int) $r['id'] ?>"><?= e($r['name']) ?></a></td>
            <td><?= e(Entries::raceLabel($r)) ?></td>
            <td><?= e(Entries::conditions($r)) ?></td>
            <td><?= e(Calls::distanceLabel((float) $r['distance_f'])) ?> <?= e($r['surface']) ?></td>
            <td class="num"><?= e(number_format((int) $r['purse'])) ?></td>
            <td class="num"><?= e(number_format((int) $r['entry_fee'])) ?></td>
            <td class="num"><?= (int) $r['field'] ?></td>
            <td><a href="race.php?id=<?= (int) $r['id'] ?>"><?= e($status($r)) ?></a></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endforeach ?>
  <p class="note">Entries close when the evening's races run. Races with fewer than 5 horses are filled with ghost horses; races with no player horses are cancelled.</p>
</section>
