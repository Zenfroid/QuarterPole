<?php use QuarterPole\Csrf; ?>
<section class="card card-body mb-3">
  <h1>Test login</h1>
  <p class="small text-body-secondary">Test mode: no forum is connected. Pick any member number to play as that player.</p>
  <form method="post" action="dev_login.php">
    <?= Csrf::field() ?>
    <label>Member number <input class="form-control" type="number" name="id" min="1" value="1" required></label>
    <label>Name <input class="form-control" type="text" name="name" value="Tester" maxlength="40"></label>
    <label class="check"><input class="form-check-input" type="checkbox" name="admin" value="1"> Admin</label>
    <button type="submit" class="btn btn-primary">Log in</button>
  </form>
</section>
