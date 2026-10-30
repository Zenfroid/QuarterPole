<?php use QuarterPole\Pages; ?>
<section class="panel page-text">
  <h1><?= e($doc['title'] ?? 'Quarter Pole') ?></h1>
  <?= $doc ? Pages::html($doc['body']) : '<p class="muted">This page has no text yet.</p>' ?>
  <?php if ($isHome): ?>
    <p class="page-cta">
      <?php if (!$member): ?>
        <a class="button" href="<?= e($auth->loginUrl()) ?>">Log in to play</a>
      <?php elseif (!$stable): ?>
        <a class="button" href="?page=dashboard">Open your stable</a>
      <?php else: ?>
        <a class="button" href="?page=dashboard">Go to your dashboard</a>
      <?php endif ?>
    </p>
  <?php endif ?>
</section>
