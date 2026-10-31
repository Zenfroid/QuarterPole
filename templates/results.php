<?php use QuarterPole\GameClock; use QuarterPole\Race\Calls; use QuarterPole\Race\Entries;
?>
<section>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Results</h1>
    <form method="get" action="results.php" class="d-flex flex-wrap gap-2">
      <select class="form-select form-select-sm w-auto" name="track" onchange="this.form.submit()">
        <option value="">All tracks</option>
        <?php foreach ($tracks as $t): ?><option value="<?= (int) $t['id'] ?>" <?= $track === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach ?>
      </select>
      <select class="form-select form-select-sm w-auto" name="surface" onchange="this.form.submit()">
        <option value="">All surfaces</option>
        <?php foreach (['dirt' => 'Dirt', 'turf' => 'Turf', 'steeplechase' => 'Steeplechase'] as $k => $label): ?>
          <option value="<?= $k ?>" <?= $surface === $k ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach ?>
      </select>
      <noscript><button class="btn btn-sm btn-primary">Show</button></noscript>
    </form>
  </div>

  <?php if (!$results): ?>
    <div class="card card-body mb-3 empty"><i class="bi bi-flag"></i><p class="mb-0">No races have been run<?= $track || $surface ? ' that match' : ' yet' ?>.</p></div>
  <?php else: ?>
    <div class="card mb-3 track-card">
      <div class="table-wrap">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Date</th><th>Track</th><th>Race</th><th>Distance</th><th>Going</th><th>Winner</th><th class="num">Purse</th><th class="num">Field</th></tr></thead>
          <tbody>
          <?php foreach ($results as $r): ?>
            <tr>
              <td><?= e((new DateTimeImmutable($r['run_on']))->format('M j')) ?><br><span class="small text-body-secondary"><?= e(GameClock::label(new DateTimeImmutable($r['game_date']))) ?></span></td>
              <td><?= e($r['track_name']) ?></td>
              <td><a href="race.php?id=<?= (int) $r['id'] ?>"><?= e($r['name']) ?></a><br><span class="small text-body-secondary"><?= e(Entries::raceLabel($r)) ?> · <?= e(Entries::conditions($r)) ?></span></td>
              <td><?= e(Calls::distanceLabel((float) $r['distance_f'])) ?> <?= surface_badge($r['surface']) ?></td>
              <td><?= e(ucfirst((string) $r['track_condition'])) ?></td>
              <td>
                <?php if ($r['winner_id']): ?>
                  <?= $r['winner_origin'] === 'ghost' ? e($r['winner_name']) . ' <span class="text-body-secondary">(ghost)</span>' : '<a href="horse.php?id=' . (int) $r['winner_id'] . '">' . e($r['winner_name']) . '</a>' ?>
                  <?php if ($r['winner_owner']): ?><br><span class="small text-body-secondary"><?= e($r['winner_owner']) ?></span><?php endif ?>
                <?php endif ?>
              </td>
              <td class="num"><?= e(number_format((int) $r['purse'])) ?></td>
              <td class="num"><?= (int) $r['field'] ?></td>
            </tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php if ($pages > 1):
        $url = fn (int $n) => 'results.php?' . http_build_query(array_filter(['track' => $track ?: null, 'surface' => $surface ?: null, 'p' => $n > 1 ? $n : null]));
        $from = max(1, $p - 2); $to = min($pages, $p + 2); ?>
      <nav aria-label="Results pages">
        <ul class="pagination justify-content-center flex-wrap">
          <li class="page-item<?= $p === 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= e($url($p - 1)) ?>">&laquo; Newer</a></li>
          <?php if ($from > 1): ?><li class="page-item"><a class="page-link" href="<?= e($url(1)) ?>">1</a></li><?php if ($from > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif ?><?php endif ?>
          <?php for ($n = $from; $n <= $to; $n++): ?>
            <li class="page-item<?= $n === $p ? ' active' : '' ?>"><a class="page-link" href="<?= e($url($n)) ?>"><?= $n ?></a></li>
          <?php endfor ?>
          <?php if ($to < $pages): ?><?php if ($to < $pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif ?><li class="page-item"><a class="page-link" href="<?= e($url($pages)) ?>"><?= $pages ?></a></li><?php endif ?>
          <li class="page-item<?= $p === $pages ? ' disabled' : '' ?>"><a class="page-link" href="<?= e($url($p + 1)) ?>">Older &raquo;</a></li>
        </ul>
      </nav>
    <?php endif ?>
    <p class="small text-body-secondary text-center"><?= number_format($total) ?> races. Open a race to see its full chart.</p>
  <?php endif ?>
</section>
