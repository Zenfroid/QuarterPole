<?php use QuarterPole\Csrf; ?>
<section class="panel">
  <h1>Name your stable</h1>
  <p>Your stable name shows on race results, sale listings and the stable list.</p>
  <form method="post" action="?page=create-stable">
    <?= Csrf::field() ?>
    <label>Stable name <input type="text" name="stable_name" minlength="3" maxlength="40" required></label>
    <button type="submit">Open my stable</button>
  </form>
</section>
