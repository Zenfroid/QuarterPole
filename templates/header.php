<?php use QuarterPole\GameClock; use QuarterPole\View; $flash = View::flash();
// The top of every page. Pages include this, then their content, then footer.php. ?>
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
  <a class="brand" href="index.php"><?= e($config['site_name']) ?></a>
  <span class="date"><?= e(GameClock::label($today)) ?></span>
  <nav>
    <a href="races.php">Races</a>
    <a href="stallions.php">Stallions</a>
    <a href="rules.php">Rules</a>
    <a href="guide.php">Guide</a>
    <?php if ($member): ?>
      <?php if ($stable): ?>
        <span class="stat"><?= e(money($stable['cash'])) ?></span>
        <span class="stat"><?= (int) $stable['creation_credits'] ?> credits</span>
      <?php endif ?>
      <details class="menu">
        <summary><?= e($member->name) ?></summary>
        <div class="menu-list">
          <a href="dashboard.php">Dashboard</a>
          <?php if ($stable): ?><a href="create_horse.php">Create a horse</a><?php endif ?>
          <?php if ($member->isAdmin): ?><a href="admin.php">Admin panel</a><?php endif ?>
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
