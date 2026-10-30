<?php use QuarterPole\Csrf; ?>
<section class="panel">
  <h1>Create a foundation horse</h1>
  <p>This uses 1 creation credit. You have <?= (int) $stable['creation_credits'] ?>.
     Stats, running style, peak age and color are rolled at random; a trainer's report comes with the horse.</p>
  <form method="post" action="create_horse.php">
    <?= Csrf::field() ?>
    <label>Name <input type="text" name="name" maxlength="18" required></label>
    <label>Sex
      <select name="sex"><option value="M">Colt / Stallion</option><option value="F">Filly / Mare</option></select>
    </label>
    <label>Surface
      <select name="surface">
        <option value="dirt">Dirt</option><option value="turf">Turf</option><option value="steeplechase">Steeplechase</option>
      </select>
    </label>
    <label>Age <input type="number" name="age" min="2" max="20" value="2" required></label>
    <p class="note">Older mares can have foals with earlier birth years (backdated foals), so they cost no more to create.</p>
    <button type="submit" <?= $stable['creation_credits'] < 1 ? 'disabled' : '' ?>>Create horse</button>
  </form>
</section>
