<?php use QuarterPole\Csrf; use QuarterPole\Pages; ?>
<section>
  <p class="muted"><a href="?page=admin">Admin panel</a> › Pages</p>
  <h1>Pages</h1>
  <nav class="filters">
    <?php foreach (Pages::SLUGS as $s => $label): ?>
      <a href="?page=admin-pages&amp;slug=<?= e($s) ?>" class="<?= $s === $slug ? 'on' : '' ?>"><?= e($label) ?></a>
    <?php endforeach ?>
  </nav>
  <form method="post" action="?page=admin-pages" class="panel">
    <?= Csrf::field() ?>
    <input type="hidden" name="slug" value="<?= e($slug) ?>">
    <label>Title <input type="text" name="title" value="<?= e($doc['title']) ?>" maxlength="100"></label>
    <label>Text <textarea name="body" rows="24"><?= e($doc['body']) ?></textarea></label>
    <p class="note">
      A blank line starts a new paragraph. <code>## Heading</code> and <code>### Smaller heading</code> make headings,
      a line starting with <code>- </code> is a bullet point, <code>**words**</code> are bold, and
      <code>[link text](?page=races)</code> makes a link.
    </p>
    <button type="submit">Save</button>
    <a href="?page=<?= $slug === 'welcome' ? 'home' : e($slug) ?>">View the page</a>
  </form>
</section>
