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
    <?php if ($member): ?>
      <?php if ($stable): ?>
        <a href="?page=studs">Stallions</a>
        <span class="stat"><?= e(money($stable['cash'])) ?></span>
        <span class="stat"><?= (int) $stable['creation_credits'] ?> credits</span>
      <?php endif ?>
      <span><?= e($member->name) ?></span>
      <a href="<?= e($auth->logoutUrl()) ?>">Log out</a>
    <?php else: ?>
      <a href="<?= e($auth->loginUrl()) ?>">Log in</a>
    <?php endif ?>
  </nav>
</header>
<main>
  <?php if ($flash): ?><p class="flash <?= e($flash['kind']) ?>"><?= e($flash['text']) ?></p><?php endif ?>
  <?= $content ?>
</main>
</body>
</html>
