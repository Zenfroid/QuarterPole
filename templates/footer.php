<?php // The bottom of every page. ?>
</main>
<footer class="site-footer bg-dark py-4 mt-5" data-bs-theme="dark">
  <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
    <span class="text-body-secondary">&copy; <?= date('Y') ?> <?= e($config['site_name']) ?> · A racehorse breeding game</span>
    <nav class="d-flex gap-3">
      <a href="rules.php">Rules</a>
      <a href="guide.php">Guide</a>
      <a href="/Forum">Forum</a>
    </nav>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
