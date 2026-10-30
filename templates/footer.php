<?php // The bottom of every page. ?>
</main>
<script>
  // Close the name menu when clicking anywhere else.
  document.addEventListener('click', e => document.querySelectorAll('details.menu[open]').forEach(d => { if (!d.contains(e.target)) d.open = false; }));
</script>
</body>
</html>
