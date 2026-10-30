<?php
// The Rules page. Edit the HTML between the header and footer lines.
require __DIR__ . '/_boot.php';
require QP_ROOT . '/templates/header.php';
?>
<section class="card card-body mb-3 page-text">
  <h1>Rules</h1>

  <h2>Your stable</h2>
  <ul>
    <li>Every player gets one stable. New stables start with Cash and creation credits for foundation horses.</li>
    <li>Cash is the game's money. You earn it from purses and spend it on entry fees, stud fees and services.</li>
    <li>Creation credits make new foundation horses. Sending a horse to the TRP gives a credit back.</li>
  </ul>

  <h2>Horses</h2>
  <ul>
    <li>Each horse has hidden stats. You never see the numbers: your trainer's reports describe them, and better trainers judge more accurately.</li>
    <li>Every horse prefers one surface: dirt, turf or steeplechase. Horses only enter races on their own surface.</li>
    <li>Horses grow stronger until their peak age, hold their peak for a few years, then slowly decline.</li>
    <li>Energy goes down when a horse races and comes back a little each day. Tired horses run worse.</li>
    <li>Horse names follow the Jockey Club rule: up to 18 characters, letters, numbers, spaces and ' . -</li>
  </ul>

  <h2>Racing</h2>
  <ul>
    <li>Races run once a day. Enter before race time; you can scratch and get your entry fee back until then.</li>
    <li>A horse can run in only one race per day.</li>
    <li>Race types: maiden (horses that have never won), claiming, allowance, and graded stakes (Grade 3, Grade 2 and Grade 1).</li>
    <li>Steeplechases are for 4-year-olds and up.</li>
    <li>Races with no player horses are cancelled. Fields with few entries are filled out with house horses, whose purses are never paid.</li>
    <li>The top five finishers share the purse.</li>
  </ul>

  <h2>Claiming races</h2>
  <ul>
    <li>Entering a claiming race puts your horse up for sale at the race's price.</li>
    <li>Other stables can put in a claim before the race. If several claim the same horse, one is picked at random and the others are refunded.</li>
    <li>You keep any purse your horse wins in that race.</li>
  </ul>

  <h2>Breeding</h2>
  <ul>
    <li>Retire a horse to breeding to stand a stallion or breed a mare. Retirement is permanent.</li>
    <li>Stallion owners set a stud fee and a book size. Your own mares breed to your own stallions for free.</li>
    <li>Coat colour follows real horse genetics. A genetic test shows a horse's genes.</li>
  </ul>

  <h2>Leaving the stable</h2>
  <ul>
    <li>Pensioning sends a horse to the retirement home. It no longer races or breeds.</li>
    <li>The TRP (Thoroughbred Rehoming Program) takes a horse out of the game and returns a creation credit. It opens once the horse has been offered in a claiming race or at auction.</li>
  </ul>

  <h2>Fair play</h2>
  <ul>
    <li>One stable per person.</li>
    <li>Be kind on the forum and in chat. Admins have the final say.</li>
  </ul>
</section>
<?php require QP_ROOT . '/templates/footer.php'; ?>
