<?php use QuarterPole\Csrf; use QuarterPole\TrainerNotes;
$isStat = str_starts_with($topic, 'stat_');
$bands = $isStat ? array_keys(TrainerNotes::BANDS) : [''];
$bandLabel = fn (string $b) => ['exceptional' => 'Exceptional (85+)', 'very_good' => 'Very good (70 to 84)', 'useful' => 'Useful (55 to 69)',
    'ordinary' => 'Ordinary (40 to 54)', 'weak' => 'Weak (under 40)'][$b] ?? '';
$byBand = [];
foreach ($lines as $l) {
    $byBand[$l['band'] ?? ''][] = $l;
}
?>
<section>
  <p class="text-body-secondary"><a href="admin.php">Admin panel</a> › Trainer's report wording</p>
  <h1>Trainer's report wording</h1>
  <p class="text-body-secondary">When a slot has several lines, the trainer picks one at random. Write <code>{name}</code> to use the horse's name. Clear a line to remove it. Reports already written keep their old wording.</p>
  <div class="report-admin">
    <nav class="topic-list">
      <?php foreach ($topics as $key => $label): ?>
        <a href="admin_reports.php?topic=<?= e($key) ?>" class="<?= $key === $topic ? 'on' : '' ?>"><?= e($label) ?> <span class="text-body-secondary"><?= (int) ($counts[$key] ?? 0) ?></span></a>
      <?php endforeach ?>
    </nav>
    <form method="post" action="admin_reports.php" class="card card-body mb-3">
      <?= Csrf::field() ?>
      <input type="hidden" name="topic" value="<?= e($topic) ?>">
      <h2><?= e($topics[$topic]) ?></h2>
      <?php if ($isStat): ?><p class="small text-body-secondary">The trainer's judgement of the stat (with their usual error) decides the band.</p><?php endif ?>
      <?php foreach ($bands as $band): ?>
        <?php if ($isStat): ?><h3><?= e($bandLabel($band)) ?></h3><?php endif ?>
        <?php foreach ($byBand[$band] ?? [] as $l): ?>
          <input class="form-control line" type="text" name="line[<?= (int) $l['id'] ?>]" value="<?= e($l['line']) ?>" maxlength="255">
        <?php endforeach ?>
        <?php if (empty($byBand[$band])): ?><p class="text-danger">No lines yet: reports will skip this.</p><?php endif ?>
        <input class="form-control line" type="text" name="add[<?= e($topic . '|' . $band) ?>]" placeholder="Add a line" maxlength="255">
      <?php endforeach ?>
      <button type="submit" class="btn btn-primary">Save</button>
    </form>
  </div>
</section>
