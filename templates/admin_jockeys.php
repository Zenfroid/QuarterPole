<?php use QuarterPole\Csrf;
$riding = count(array_filter($jockeys, fn ($j) => $j['active']));
?>
<section>
  <p class="text-body-secondary"><a href="admin.php">Admin panel</a> › Jockeys</p>
  <h1>Jockeys</h1>
  <p class="text-body-secondary">Each horse in a race gets a different jockey, picked at random from those riding. All jockeys ride equally well for now. Untick Riding to retire a jockey: they get no new rides, and old race charts keep their name.</p>
  <?php if ($riding < $maxField): ?>
    <p class="text-danger">Only <?= $riding ?> jockeys are riding, but a race can have <?= $maxField ?> horses, so some jockeys will ride twice in a full race.</p>
  <?php endif ?>
  <form method="post" action="admin_jockeys.php" class="card card-body mb-3">
    <?= Csrf::field() ?>
    <div class="table-wrap">
      <table class="table table-hover align-middle">
        <thead><tr><th>Name</th><th>Riding</th><th class="num">Rides</th><th class="num">Wins</th></tr></thead>
        <tbody>
        <?php foreach ($jockeys as $j): ?>
          <tr>
            <td><input class="form-control line" type="text" name="name[<?= (int) $j['id'] ?>]" value="<?= e($j['name']) ?>" maxlength="60"></td>
            <td><input class="form-check-input" type="checkbox" name="active[<?= (int) $j['id'] ?>]" value="1" <?= $j['active'] ? 'checked' : '' ?>></td>
            <td class="num"><?= (int) $j['rides'] ?></td>
            <td class="num"><?= (int) $j['wins'] ?></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <h3>Add jockeys</h3>
    <?php for ($i = 0; $i < 3; $i++): ?>
      <input class="form-control line" type="text" name="add[]" placeholder="New jockey's name" maxlength="60">
    <?php endfor ?>
    <button type="submit" class="btn btn-primary">Save</button>
  </form>
</section>
