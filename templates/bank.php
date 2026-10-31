<?php use QuarterPole\Bank; use QuarterPole\Csrf; ?>
<section>
  <h1>Bank</h1>
  <p class="lead">Balance: <strong><?= e(money($stable['cash'])) ?></strong></p>

  <div class="row g-3">
    <div class="col-lg-4">
      <form method="post" action="bank.php" class="card card-body mb-3">
        <?= Csrf::field() ?>
        <h2><i class="bi bi-send"></i> Send Cash</h2>
        <label>To stable
          <input class="form-control" type="text" name="to" list="stableNames" maxlength="40" required autocomplete="off">
          <datalist id="stableNames"><?php foreach ($others as $o): ?><option value="<?= e($o['stable_name']) ?>"><?php endforeach ?></datalist>
        </label>
        <label>Amount <input class="form-control" type="text" name="amount" inputmode="numeric" placeholder="25,000" required></label>
        <label>Note (optional) <input class="form-control" type="text" name="note" maxlength="120" placeholder="For the stud fee"></label>
        <div><button type="submit" class="btn btn-primary" onclick="return confirm('Send this Cash? It can\'t be taken back.')">Send</button></div>
        <p class="small text-body-secondary mt-2 mb-0">Sending is instant and can't be undone. Both stables see it in their history.</p>
      </form>
    </div>
    <div class="col-lg-8">
      <div class="card mb-3 track-card">
        <h2 class="card-header"><i class="bi bi-clock-history"></i> Cash history</h2>
        <?php if (!$history): ?>
          <p class="card-body mb-0">Nothing yet.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table class="table table-hover align-middle mb-0 wrap">
              <thead><tr><th>Date</th><th>What</th><th class="num">Amount</th><th class="num">Balance</th></tr></thead>
              <tbody>
              <?php foreach ($history as $t): ?>
                <tr>
                  <td class="text-nowrap"><?= e(date('M j', strtotime($t['created_at']))) ?></td>
                  <td><?= e(Bank::KINDS[$t['kind']] ?? $t['kind']) ?><?php if ($t['note']): ?><br><span class="small text-body-secondary"><?= e($t['note']) ?></span><?php endif ?></td>
                  <td class="num text-nowrap <?= $t['amount'] < 0 ? 'text-danger' : 'text-success' ?>"><?= $t['amount'] < 0 ? '−' : '+' ?><?= e(number_format(abs((int) $t['amount']))) ?></td>
                  <td class="num text-nowrap"><?= e(number_format((int) $t['balance_after'])) ?></td>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>
        <?php endif ?>
      </div>
    </div>
  </div>
</section>
