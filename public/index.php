<?php
// The home page: a welcome for visitors and players. Edit the HTML between the header and footer lines.
require __DIR__ . '/_boot.php';
require QP_ROOT . '/templates/header.php';
?>
<section class="hero mb-4">
  <div class="hero-text">
    <p class="hero-kicker">Thoroughbred racing and breeding</p>
    <h1>Welcome to Quarter Pole</h1>
    <p class="lead">Breed better racehorses from foundation stock, race them for Cash and glory, and build a stable people talk about.</p>
    <div class="d-flex flex-wrap gap-2 mt-4">
      <?php if (!$member): ?>
        <a class="btn btn-primary btn-lg" href="<?= e($auth->loginUrl()) ?>">Log in to play</a>
      <?php elseif (!$stable): ?>
        <a class="btn btn-primary btn-lg" href="dashboard.php">Open your stable</a>
      <?php else: ?>
        <a class="btn btn-primary btn-lg" href="dashboard.php">Go to your dashboard</a>
      <?php endif ?>
      <a class="btn btn-outline-light btn-lg" href="guide.php">How to play</a>
    </div>
  </div>
</section>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card feature h-100"><div class="card-body">
      <i class="bi bi-stars"></i>
      <h2>Build a stable</h2>
      <p>Create foundation horses with your creation credits, or claim them from other stables.</p>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card feature h-100"><div class="card-body">
      <i class="bi bi-clipboard-data"></i>
      <h2>Train</h2>
      <p>Train every day, read your trainer's reports, and find the races that suit each horse.</p>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card feature h-100"><div class="card-body">
      <i class="bi bi-flag"></i>
      <h2>Race</h2>
      <p>Race on dirt, turf and over jumps at tracks with their own personalities.</p>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card feature h-100"><div class="card-body">
      <i class="bi bi-heart"></i>
      <h2>Breed</h2>
      <p>Retire your best runners to breeding, and breed the next champion.</p>
    </div></div>
  </div>
</div>

<section class="card card-body mb-3 page-text">
  <h2>Game time</h2>
  <p>The game runs at twice real speed: every real day is two game days, and every horse has its birthday on the first of January. Races run once a day.</p>
  <p class="mb-0"><a href="rules.php">Read the rules</a>, <a href="guide.php">follow the guide</a>, or join the conversation on the <a href="/Forum">forum</a>.</p>
</section>
<?php require QP_ROOT . '/templates/footer.php'; ?>
