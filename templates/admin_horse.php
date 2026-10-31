<?php use QuarterPole\AdminHorses; use QuarterPole\Csrf;
$select = function (string $col) use ($horse): string {
    $html = '<select class="form-select" name="' . e($col) . '">';
    foreach (AdminHorses::CHOICES[$col] as $v => $label) {
        $html .= '<option value="' . e($v) . '"' . ((string) ($horse[$col] ?? '') === (string) $v ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return $html . '</select>';
};
$check = fn (string $col, string $label) => '<label class="check"><input class="form-check-input" type="checkbox" name="' . e($col) . '" value="1"' . ($horse[$col] ? ' checked' : '') . '> ' . e($label) . '</label>';
$stats = array_filter(AdminHorses::NUMBERS, fn ($k) => str_starts_with($k, 'stat_'), ARRAY_FILTER_USE_KEY);
$other = array_diff_key(AdminHorses::NUMBERS, $stats);
?>
<section>
  <p class="text-body-secondary"><a href="admin.php">Admin panel</a> › <a href="admin_horses.php">Horses</a> › <?= e($horse['name']) ?></p>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1>Edit <?= e($horse['name']) ?> <span class="text-body-secondary">#<?= (int) $horse['id'] ?></span></h1>
    <a class="btn btn-primary" href="horse.php?id=<?= (int) $horse['id'] ?>">View the horse's page</a>
  </div>
  <p class="small text-body-secondary">Changes save straight to the horse. Trainer's reports already written keep their wording; stats are on the 25 to 100 scale players never see.</p>
  <form method="post" action="admin_horse.php">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $horse['id'] ?>">
    <div class="card card-body mb-3">
      <h2>Basics</h2>
      <div class="form-grid">
        <label>Name <input class="form-control" type="text" name="name" value="<?= e($horse['name']) ?>" maxlength="18"></label>
        <label>Owner (forum member number, blank for none) <input class="form-control" type="text" name="owner_id" value="<?= e((string) ($horse['owner_id'] ?? '')) ?>"></label>
        <label>Status <?= $select('status') ?></label>
        <label>Sex <?= $select('sex') ?></label>
        <label>Best surface (hidden) <?= $select('surface_pref') ?></label>
        <label>Racing surface (owner's choice) <?= $select('racing_surface') ?></label>
        <label>Running style <?= $select('running_style') ?></label>
        <label>Stud fee (blank = not standing) <input class="form-control" type="text" name="stud_fee" value="<?= e((string) ($horse['stud_fee'] ?? '')) ?>"></label>
      </div>
      <div class="d-flex flex-wrap gap-2 mb-2">
        <?= $check('gelded', 'Gelded') ?>
        <?= $check('offered_for_sale', 'Has been offered for sale (opens the TRP)') ?>
        <?= $check('genetics_tested', 'Genetics tested (genes shown)') ?>
      </div>
    </div>
    <div class="card card-body mb-3">
      <h2>Stats</h2>
      <div class="form-grid">
        <?php foreach ($stats as $col => [$label, $min, $max]): ?>
          <label><?= e($label) ?> <input class="form-control" type="number" name="<?= e($col) ?>" min="<?= $min ?>" max="<?= $max ?>" value="<?= (int) $horse[$col] ?>"></label>
        <?php endforeach ?>
        <?php foreach (AdminHorses::DECIMALS as $col => [$label, $min, $max]): ?>
          <label><?= e($label) ?> <input class="form-control" type="number" name="<?= e($col) ?>" min="<?= $min ?>" max="<?= $max ?>" step="0.01" value="<?= e((string) $horse[$col]) ?>"></label>
        <?php endforeach ?>
      </div>
    </div>
    <div class="card card-body mb-3">
      <h2>Condition and record</h2>
      <div class="form-grid">
        <?php foreach ($other as $col => [$label, $min, $max]): ?>
          <label><?= e($label) ?> <input class="form-control" type="number" name="<?= e($col) ?>" min="<?= $min ?>" max="<?= $max ?>" value="<?= (int) $horse[$col] ?>"></label>
        <?php endforeach ?>
      </div>
    </div>
    <button type="submit" class="btn btn-primary">Save changes</button>
  </form>
</section>
