<?php use QuarterPole\Csrf; ?>
<section class="panel">
  <h1>Test login</h1>
  <p class="note">Test mode: no forum is connected. Pick any member number to play as that player.</p>
  <form method="post" action="dev_login.php">
    <?= Csrf::field() ?>
    <label>Member number <input type="number" name="id" min="1" value="1" required></label>
    <label>Name <input type="text" name="name" value="Tester" maxlength="40"></label>
    <label class="check"><input type="checkbox" name="admin" value="1"> Admin</label>
    <button type="submit">Log in</button>
  </form>
</section>
