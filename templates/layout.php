<?php use QuarterPole\GameClock; use QuarterPole\View; $flash = View::flash(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($config['site_name']) ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site">
  <a class="brand" href="?"><?= e($config['site_name']) ?></a>
  <span class="date"><?= e(GameClock::label($today)) ?></span>
  <nav>
    <a href="?page=races">Races</a>
    <a href="?page=studs">Stallions</a>
    <a href="?page=rules">Rules</a>
    <a href="?page=guide">Guide</a>
    <?php if ($member): ?>
      <?php if ($stable): ?>
        <span class="stat"><?= e(money($stable['cash'])) ?></span>
        <span class="stat"><?= (int) $stable['creation_credits'] ?> credits</span>
      <?php endif ?>
      <details class="menu">
        <summary><?= e($member->name) ?></summary>
        <div class="menu-list">
          <a href="?page=dashboard">Dashboard</a>
          <?php if ($stable): ?><a href="?page=create-horse">Create a horse</a><?php endif ?>
          <?php if ($member->isAdmin): ?><a href="?page=admin">Admin panel</a><?php endif ?>
          <a href="<?= e($auth->logoutUrl()) ?>">Log out</a>
        </div>
      </details>
    <?php else: ?>
      <a href="<?= e($auth->loginUrl()) ?>">Log in</a>
    <?php endif ?>
  </nav>
</header>
<main>
  <?php if ($flash): ?><p class="flash <?= e($flash['kind']) ?>"><?= e($flash['text']) ?></p><?php endif ?>
  <?= $content ?>
</main>
<script>
  // Close the name menu when clicking anywhere else.
  document.addEventListener('click', e => document.querySelectorAll('details.menu[open]').forEach(d => { if (!d.contains(e.target)) d.open = false; }));
</script>
</body>
</html>
