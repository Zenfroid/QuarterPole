<?php use QuarterPole\GameClock; use QuarterPole\View; $flash = View::flash();
// The top of every page. Pages include this, then their content, then footer.php. ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($config['site_name']) ?></title>
  <!-- Bootstrap 5. To use a Bootswatch theme instead, swap this line for the theme's, e.g.
       https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/flatly/bootstrap.min.css -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php"><?= e($config['site_name']) ?></a>
    <span class="navbar-text text-body-secondary me-3"><?= e(GameClock::label($today)) ?></span>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="races.php">Races</a></li>
        <li class="nav-item"><a class="nav-link" href="stallions.php">Stallions</a></li>
        <li class="nav-item"><a class="nav-link" href="rules.php">Rules</a></li>
        <li class="nav-item"><a class="nav-link" href="guide.php">Guide</a></li>
      </ul>
      <ul class="navbar-nav align-items-lg-center">
        <?php if ($member): ?>
          <?php if ($stable): ?>
            <li class="nav-item"><span class="navbar-text me-3"><?= e(money($stable['cash'])) ?></span></li>
            <li class="nav-item"><span class="navbar-text me-3"><?= (int) $stable['creation_credits'] ?> credits</span></li>
          <?php endif ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= e($member->name) ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
              <?php if ($stable): ?><li><a class="dropdown-item" href="create_horse.php">Create a horse</a></li><?php endif ?>
              <?php if ($member->isAdmin): ?><li><a class="dropdown-item" href="admin.php">Admin panel</a></li><?php endif ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= e($auth->logoutUrl()) ?>">Log out</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="btn btn-primary btn-sm" href="<?= e($auth->loginUrl()) ?>">Log in</a></li>
        <?php endif ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-4">
  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['kind'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
      <?= e($flash['text']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif ?>
