<?php use QuarterPole\Csrf; ?>
<section class="card card-body mb-3">
  <h1>Name your stable</h1>
  <p>Your stable name shows on race results, sale listings and the stable list.</p>
  <form method="post" action="dashboard.php">
    <?= Csrf::field() ?>
    <label>Stable name <input class="form-control" type="text" name="stable_name" minlength="3" maxlength="40" required></label>
    <button type="submit" class="btn btn-primary">Open my stable</button>
  </form>
</section>
