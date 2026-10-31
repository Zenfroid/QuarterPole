<?php
// Admin: shows which copy of the game's files the server is actually running, and clears PHP's code cache.
// Upload-only helper; it changes nothing but the cache.
require __DIR__ . '/_boot.php';
need_admin();

// Fingerprints of the Oct 31 fix-list release.
$expected = [
    'quarterpole/src/Race/TrackConditions.php' => '66e8c523ade580947cc801838dc19144',
    'quarterpole/src/Bank.php' => 'daeb7d34b39be69c1b575e52f2933918',
    'quarterpole/src/Stable.php' => 'e74d7a8ad2609c745789d3ac64bd192e',
    'quarterpole/src/bootstrap.php' => '23f7f00c7a6b6eebd886ca9f4064a3bf',
    'quarterpole/src/helpers.php' => 'bfb1b18538376f4a908ec8198cd7327f',
    'quarterpole/src/Genetics/Color.php' => '14dc37e3ac3066fe309627272bd69873',
    'quarterpole/src/Race/Schedule.php' => '0d978bde3db58acb38ad81ddd153cccb',
    'quarterpole/src/Race/Runner.php' => '2ffc375deb2c16fb4b5410ccfcd32d64',
    'quarterpole/templates/header.php' => 'e4d3e3292ace49bc22743cf38ed09885',
    'quarterpole/templates/races.php' => 'f0dd2737cf45ebf81f48c6e2b223cef1',
    'quarterpole/templates/results.php' => 'bad707dc9c5a091427311cfd47dd01fd',
    'quarterpole/templates/dashboard.php' => '7abead19d3182e7b53ac006d92c909d1',
    'quarterpole/templates/breeding.php' => 'adcfef3b006bffaf5bd408d7c840c574',
    'quarterpole/templates/bank.php' => '3c2604ef6cab5a1ce8dd0e712b2a90df',
    'public_html/_boot.php' => 'd0dc99064865f5ffcc2d49ba8497e8cc',
    'public_html/races.php' => '117ad98e19427979b7914a6351565869',
    'public_html/results.php' => '8234b70e01efb6ac6baba14c2ccd6688',
    'public_html/dashboard.php' => '15e8ad3505188e8edd0f2e786b26008b',
    'public_html/breeding.php' => 'fbe5c926a408009c524115cc98cd6383',
    'public_html/bank.php' => 'c6b664dcf98b27b91a2e7469b0c4d8c7',
    'public_html/site.css' => '291352195c3e28602908590f2bc57546',
    'quarterpole/src/HorseFactory.php' => '516c14fde93d1557d9730646905130e3',
    'quarterpole/src/HorseActions.php' => '0cc93c9c69aecd80e198ef88ef2d5eb5',
    'quarterpole/src/Race/Entries.php' => '0d9a40fa4230e3a7d7cd6f905a69e2dc',
    'quarterpole/src/Breeding.php' => '95b2a389dd1f93a5a4f1d8912420f0e5',
    'quarterpole/src/AdminHorses.php' => '748c3f23ed5884b6e3b4c547d1eecf24',
    'quarterpole/templates/horse.php' => '42760558610aa1e0446935962959917d',
    'quarterpole/templates/create_horse.php' => '2ea17cf857eb56f16ac06238458fa5ee',
    'public_html/horse_action.php' => 'e42618ff06d17d0efe353ef1c44cb93c',
    'public_html/create_horse.php' => '38632f96815c3ebcf328ca6e1fef3fc7',
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
