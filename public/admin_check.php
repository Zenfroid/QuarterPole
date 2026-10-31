<?php
// Admin: shows which copy of the game's files the server is actually running, and clears PHP's code cache.
// Upload-only helper; it changes nothing but the cache.
require __DIR__ . '/_boot.php';
need_admin();

// Fingerprints of the Oct 31 fix-list release.
$expected = [
    'quarterpole/src/Race/TrackConditions.php' => '66e8c523ade580947cc801838dc19144',
    'quarterpole/src/Bank.php' => 'daeb7d34b39be69c1b575e52f2933918',
    'quarterpole/src/Stable.php' => 'a6c328611e85db97f001b22760d9b246',
    'quarterpole/src/bootstrap.php' => '23f7f00c7a6b6eebd886ca9f4064a3bf',
    'quarterpole/src/helpers.php' => '9ed78c9a033b891fc7117c38cf117321',
    'quarterpole/src/Genetics/Color.php' => '14dc37e3ac3066fe309627272bd69873',
    'quarterpole/src/Race/Schedule.php' => '0d978bde3db58acb38ad81ddd153cccb',
    'quarterpole/src/Race/Runner.php' => '12d01c0d959ae240091adc5eacc039f1',
    'quarterpole/templates/header.php' => 'e4d3e3292ace49bc22743cf38ed09885',
    'quarterpole/templates/races.php' => 'f0dd2737cf45ebf81f48c6e2b223cef1',
    'quarterpole/templates/results.php' => 'bad707dc9c5a091427311cfd47dd01fd',
    'quarterpole/templates/dashboard.php' => 'abb4159b161dae470f31d50ad930d778',
    'quarterpole/templates/breeding.php' => '4c880c12423a05293043fb4126c2a351',
    'quarterpole/templates/bank.php' => '3c2604ef6cab5a1ce8dd0e712b2a90df',
    'public_html/_boot.php' => 'd0dc99064865f5ffcc2d49ba8497e8cc',
    'public_html/races.php' => '117ad98e19427979b7914a6351565869',
    'public_html/results.php' => '8234b70e01efb6ac6baba14c2ccd6688',
    'public_html/dashboard.php' => '15e8ad3505188e8edd0f2e786b26008b',
    'public_html/breeding.php' => 'fbe5c926a408009c524115cc98cd6383',
    'public_html/bank.php' => 'c6b664dcf98b27b91a2e7469b0c4d8c7',
    'public_html/site.css' => '08f0de04268118e8667c2cccdeeb70bc',
];
if ($isPost && function_exists('opcache_reset')) {
    opcache_reset();
    \QuarterPole\View::flash('PHP code cache cleared.');
    \QuarterPole\View::redirect('admin_check.php');
}
$rows = [];
foreach ($expected as $name => $md5) {
    $path = str_starts_with($name, 'public_html/') ? __DIR__ . '/' . substr($name, 12) : QP_ROOT . '/' . substr($name, 12);
    $have = is_file($path) ? md5_file($path) : null;
    $rows[] = [$name, $path, $have === null ? 'MISSING' : ($have === $md5 ? 'OK, new version' : 'OLD or different'), $have ? date('Y-m-d H:i', filemtime($path)) : ''];
}
$opcache = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
require QP_ROOT . '/templates/header.php';
?>
<section class="card card-body mb-3">
  <h1>File check</h1>
  <p>The game code is being read from: <code><?= e(QP_ROOT) ?></code></p>
  <div class="table-wrap"><table class="table table-sm">
    <thead><tr><th>File</th><th>Status</th><th>Modified</th><th>Full path</th></tr></thead>
    <?php foreach ($rows as [$n, $p, $s, $m]): ?>
      <tr><td><?= e($n) ?></td><td class="<?= $s === 'OK, new version' ? 'text-success' : 'text-danger' ?>"><strong><?= e($s) ?></strong></td><td><?= e($m) ?></td><td><small><?= e($p) ?></small></td></tr>
    <?php endforeach ?>
  </table></div>
  <p>PHP code cache (OPcache): <strong><?= $opcache && !empty($opcache['opcache_enabled']) ? 'on' : 'off' ?></strong></p>
  <?php if (function_exists('opcache_reset')): ?>
    <form method="post"><?= \QuarterPole\Csrf::field() ?><button class="btn btn-primary">Clear PHP code cache</button></form>
  <?php endif ?>
</section>
<?php require QP_ROOT . '/templates/footer.php'; ?>
