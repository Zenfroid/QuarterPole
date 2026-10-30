<?php
// The home page: a welcome for visitors and players. Edit the HTML between the header and footer lines.
require __DIR__ . '/_boot.php';
require QP_ROOT . '/templates/header.php';
?>
<section class="card card-body mb-3 page-text">
  <h1>Welcome to Quarter Pole</h1>

  <p>Breed better racehorses from foundation stock, race them for Cash and glory, and build a stable people talk about.</p>

  <h2>What you do</h2>
  <ul>
    <li>Create foundation horses with your creation credits, or claim them from other stables.</li>
    <li>Train them, read your trainer's reports, and find the races that suit them.</li>
    <li>Race on dirt, turf and over jumps at tracks with their own personalities.</li>
    <li>Retire your best runners to breeding, and breed the next champion.</li>
  </ul>

  <h2>Game time</h2>
  <p>The game runs at twice real speed: every real day is two game days, and every horse has its birthday on the first of January. Races run once a day.</p>
  <p><a href="rules.php">Read the rules</a> or <a href="guide.php">follow the guide</a> to get started.</p>
  <p>Join the conversation on the <a href="/Forum">forum</a>.</p>

  <p class="page-cta">
    <?php if (!$member): ?>
      <a class="btn btn-primary" href="<?= e($auth->loginUrl()) ?>">Log in to play</a>
    <?php elseif (!$stable): ?>
      <a class="btn btn-primary" href="dashboard.php">Open your stable</a>
    <?php else: ?>
      <a class="btn btn-primary" href="dashboard.php">Go to your dashboard</a>
    <?php endif ?>
  </p>
</section>
<?php require QP_ROOT . '/templates/footer.php'; ?>
