<section>
  <h1>Admin panel</h1>
  <p class="muted">Only forum admins can see this. <?= (int) $counts['stables'] ?> stables, <?= (int) $counts['horses'] ?> active or breeding horses.</p>
  <div class="cards admin-cards">
    <a class="card" href="?page=admin-horses"><strong>Horses</strong><span class="muted">Find any horse and edit its name, owner, stats and record.</span></a>
    <a class="card" href="?page=admin-settings"><strong>Game settings</strong><span class="muted">Every tunable number: Cash, prices, purses, race and breeding rules.</span></a>
    <a class="card" href="?page=admin-pages"><strong>Pages</strong><span class="muted">The welcome page, Rules and Guide text.</span></a>
    <a class="card" href="?page=admin-stakes"><strong>Stakes calendar</strong><span class="muted"><?= (int) $counts['stakes'] ?> named stakes that run every game year.</span></a>
    <a class="card" href="?page=admin-jockeys"><strong>Jockeys</strong><span class="muted"><?= (int) $counts['jockeys'] ?> riding. Add, rename and retire jockeys.</span></a>
    <a class="card" href="?page=admin-reports"><strong>Trainer's report wording</strong><span class="muted">The lines trainers use to describe each stat and style.</span></a>
  </div>
</section>
