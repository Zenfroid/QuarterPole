<?php use QuarterPole\Csrf;
$riding = count(array_filter($jockeys, fn ($j) => $j['active']));
?>
<section>
  <p class="muted"><a href="?page=admin-settings">Game settings</a> · <a href="?page=admin-reports">Trainer's report wording</a> · <a href="?page=admin-stakes">Stakes calendar</a> · Jockeys</p>
  <h1>Jockeys</h1>
  <p class="muted">Each horse in a race gets a different jockey, picked at random from those riding. All jockeys ride equally well for now. Untick Riding to retire a jockey: they get no new rides, and old race charts keep their name.</p>
  <?php if ($riding < $maxField): ?>
    <p class="warn">Only <?= $riding ?> jockeys are riding, but a race can have <?= $maxField ?> horses, so some jockeys will ride twice in a full race.</p>
  <?php endif ?>
  <form method="post" action="?page=admin-jockeys" class="panel">
    <?= Csrf::field() ?>
    <div class="table-wrap">
      <table class="list">
        <thead><tr><th>Name</th><th>Riding</th><th class="num">Rides</th><th class="num">Wins</th></tr></thead>
        <tbody>
        <?php foreach ($jockeys as $j): ?>
          <tr>
            <td><input class="line" type="text" name="name[<?= (int) $j['id'] ?>]" value="<?= e($j['name']) ?>" maxlength="60"></td>
            <td><input type="checkbox" name="active[<?= (int) $j['id'] ?>]" value="1" <?= $j['active'] ? 'checked' : '' ?>></td>
            <td class="num"><?= (int) $j['rides'] ?></td>
            <td class="num"><?= (int) $j['wins'] ?></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <h3>Add jockeys</h3>
    <?php for ($i = 0; $i < 3; $i++): ?>
      <input class="line" type="text" name="add[]" placeholder="New jockey's name" maxlength="60">
    <?php endfor ?>
    <button type="submit">Save</button>
  </form>
</section>
