<?php use QuarterPole\GameClock; use QuarterPole\View; $flash = View::flash();
// The top of every page. Pages include this, then their content, then footer.php. ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($config['site_name']) ?></title>
  <!-- Bootswatch "United" theme on Bootstrap 5. To change theme, swap "united" for another
       Bootswatch name, e.g. https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/flatly/bootstrap.min.css -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/united/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- The ?v= number changes whenever site.css is uploaded, so browsers fetch the new copy. -->
  <link rel="stylesheet" href="site.css?v=<?= (int) @filemtime(dirname($_SERVER['SCRIPT_FILENAME']) . '/site.css') ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-dark site-nav" data-bs-theme="dark">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <svg class="logo" viewBox="0 0 32 32" aria-hidden="true"><path d="M7 28V15a9 9 0 0 1 18 0v13h-5V15a4 4 0 0 0-8 0v13z" fill="currentColor"/><circle cx="9.5" cy="18" r="1.1" class="nail"/><circle cx="9.5" cy="23" r="1.1" class="nail"/><circle cx="22.5" cy="18" r="1.1" class="nail"/><circle cx="22.5" cy="23" r="1.1" class="nail"/><circle cx="11" cy="11" r="1.1" class="nail"/><circle cx="21" cy="11" r="1.1" class="nail"/></svg>
      <span><?= e($config['site_name']) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link<?= nav_active('races.php', 'race.php') ?>" href="races.php"><i class="bi bi-flag"></i> Races</a></li>
        <li class="nav-item"><a class="nav-link<?= nav_active('stallions.php', 'breed.php') ?>" href="stallions.php"><i class="bi bi-stars"></i> Stallions</a></li>
        <li class="nav-item"><a class="nav-link<?= nav_active('rules.php') ?>" href="rules.php"><i class="bi bi-journal-text"></i> Rules</a></li>
        <li class="nav-item"><a class="nav-link<?= nav_active('guide.php') ?>" href="guide.php"><i class="bi bi-signpost-split"></i> Guide</a></li>
      </ul>
      <ul class="navbar-nav align-items-lg-center gap-lg-2">
        <li class="nav-item"><span class="navbar-text game-date"><i class="bi bi-calendar3"></i> <?= e(GameClock::label($today)) ?></span></li>
        <?php if ($member): ?>
          <?php if ($stable): ?>
            <li class="nav-item"><a class="navbar-text stat-pill text-decoration-none" href="bank.php" title="Cash: open the Bank"><i class="bi bi-cash-coin"></i> <?= e(number_format((int) $stable['cash'])) ?></a></li>
            <li class="nav-item"><span class="navbar-text stat-pill" title="Creation credits"><i class="bi bi-ticket-perforated"></i> <?= (int) $stable['creation_credits'] ?></span></li>
          <?php endif ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle<?= nav_active('dashboard.php', 'create_horse.php', 'bank.php') ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person-circle"></i> <?= e($member->name) ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a></li>
              <?php if ($stable): ?>
                <li><a class="dropdown-item" href="create_horse.php"><i class="bi bi-plus-circle"></i> Create a horse</a></li>
                <li><a class="dropdown-item" href="bank.php"><i class="bi bi-bank"></i> Bank</a></li>
              <?php endif ?>
              <?php if ($member->isAdmin): ?><li><a class="dropdown-item" href="admin.php"><i class="bi bi-gear"></i> Admin panel</a></li><?php endif ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= e($auth->logoutUrl()) ?>"><i class="bi bi-box-arrow-right"></i> Log out</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="btn btn-primary btn-sm" href="<?= e($auth->loginUrl()) ?>">Log in</a></li>
        <?php endif ?>
      </ul>
    </div>
  </div>
</nav>
<div class="rail" aria-hidden="true"></div>
<main class="container py-4">
  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['kind'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show shadow-sm" role="alert">
      <i class="bi <?= $flash['kind'] === 'error' ? 'bi-exclamation-triangle' : 'bi-check-circle' ?>"></i>
      <?= e($flash['text']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif ?>
