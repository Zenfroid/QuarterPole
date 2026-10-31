<section>
  <h1>Admin panel</h1>
  <p class="text-body-secondary">Only forum admins can see this. <?= (int) $counts['stables'] ?> stables, <?= (int) $counts['horses'] ?> active or breeding horses.</p>
  <div class="cards admin-cards">
    <a class="card card-body" href="admin_horses.php"><i class="bi bi-search admin-icon"></i><strong>Horses</strong><span class="text-body-secondary">Find any horse and edit its name, owner, stats and record.</span></a>
    <a class="card card-body" href="admin_settings.php"><i class="bi bi-sliders admin-icon"></i><strong>Game settings</strong><span class="text-body-secondary">Every tunable number: Cash, prices, purses, race and breeding rules.</span></a>
    <a class="card card-body" href="admin_stakes.php"><i class="bi bi-calendar-event admin-icon"></i><strong>Stakes calendar</strong><span class="text-body-secondary"><?= (int) $counts['stakes'] ?> named stakes that run every game year.</span></a>
    <a class="card card-body" href="admin_jockeys.php"><i class="bi bi-person-badge admin-icon"></i><strong>Jockeys</strong><span class="text-body-secondary"><?= (int) $counts['jockeys'] ?> riding. Add, rename and retire jockeys.</span></a>
    <a class="card card-body" href="admin_reports.php"><i class="bi bi-chat-square-text admin-icon"></i><strong>Trainer's report wording</strong><span class="text-body-secondary">The lines trainers use to describe each stat and style.</span></a>
  </div>
</section>
