<?php
declare(strict_types=1);

namespace QuarterPole;

// Owner actions on the horse page: retire to breeding, stud settings, genetic test,
// trainer report upgrades, pensioning and TRP rehoming.
final class HorseActions
{
    public function __construct(private Db $db, private Settings $settings) {}

    private function owned(Db $db, int $memberId, int $horseId): array
    {
        $h = $db->one('SELECT * FROM qp_horses WHERE id = ? FOR UPDATE', [$horseId]);
        if (!$h || (int) $h['owner_id'] !== $memberId) {
            throw new UserError('That is not your horse.');
        }
        return $h;
    }

    /** Active to Retired (breeding). One way only: retired horses never race again. */
    public function retire(int $memberId, int $horseId): string
    {
        return $this->db->transaction(function (Db $db) use ($memberId, $horseId) {
            $h = $this->owned($db, $memberId, $horseId);
            if ($h['status'] !== 'active') {
                throw new UserError('Only active horses can be retired to breeding.');
            }
            $this->assertNotEntered($db, $h);
            $db->run("UPDATE qp_horses SET status = 'retired' WHERE id = ?", [$horseId]);
            return $h['name'] . ' is retired to breeding.';
        });
    }

    /** Stand a retired stallion at stud (fee set) or take him off (fee null). */
    public function setStud(int $memberId, int $horseId, ?int $fee, int $book): string
    {
        return $this->db->transaction(function (Db $db) use ($memberId, $horseId, $fee, $book) {
            $h = $this->owned($db, $memberId, $horseId);
            if ($h['sex'] !== 'M' || $h['gelded'] || $h['status'] !== 'retired') {
                throw new UserError('Only retired stallions can stand at stud.');
            }
            if ($fee !== null && ($fee < 0 || $fee > 10_000_000)) {
                throw new UserError('Stud fees must be between 0 and 10,000,000 Cash.');
            }
            $cap = $this->settings->int('book_cap_player');
            if ($book < 1 || $book > $cap) {
                throw new UserError("Book size must be 1 to $cap mares.");
            }
            $db->run('UPDATE qp_horses SET stud_fee = ?, book_size = ? WHERE id = ?', [$fee, $book, $horseId]);
            return $fee === null ? $h['name'] . ' is no longer standing at stud.' : $h['name'] . ' is standing at stud for ' . money($fee) . '.';
        });
    }

    /** Pay for a genetic test: reveals the color genotype to everyone. */
    public function geneticTest(int $memberId, int $horseId): string
    {
        return $this->db->transaction(function (Db $db) use ($memberId, $horseId) {
            $h = $this->owned($db, $memberId, $horseId);
            if ($h['genetics_tested']) {
                throw new UserError($h['name'] . ' has already been tested.');
            }
            (new Ledger($db))->move($memberId, -$this->settings->int('genetic_test_cost'), 'genetic_test', 'qp_horses', $horseId, 'Genetic test: ' . $h['name']);
            $db->run('UPDATE qp_horses SET genetics_tested = 1 WHERE id = ?', [$horseId]);
            return 'The genetic test results for ' . $h['name'] . ' are in.';
        });
    }

    /** Hire a better trainer. Each level is written once and never redone. */
    public function upgradeReport(int $memberId, int $horseId, string $level, \DateTimeImmutable $gameToday): string
    {
        return $this->db->transaction(function (Db $db) use ($memberId, $horseId, $level, $gameToday) {
            $h = $this->owned($db, $memberId, $horseId);
            $order = ['none' => 0, 'basic' => 1, 'experienced' => 2, 'expert' => 3];
            if (!in_array($level, ['experienced', 'expert'], true)) {
                throw new UserError('Pick a trainer.');
            }
            if (GameClock::ageOn($h['birth_game_date'], $gameToday) < 2) {
                throw new UserError('Trainers only report on horses aged 2 or older.');
            }
            if ($order[$level] <= $order[$h['report_level']]) {
                throw new UserError($h['name'] . ' already has a report at that level or better.');
            }
            (new Ledger($db))->move($memberId, -$this->settings->int("report_cost_$level"), 'trainer_report', 'qp_horses', $horseId,
                ucfirst($level) . ' trainer: ' . $h['name']);
            $db->run('INSERT INTO qp_trainer_reports (horse_id, level, notes) VALUES (?, ?, ?)', [$horseId, $level, TrainerNotes::write($db, $h, $level)]);
            $db->run('UPDATE qp_horses SET report_level = ? WHERE id = ?', [$level, $horseId]);
            return 'The ' . ($level === 'expert' ? 'Expert' : 'Experienced') . ' trainer has written up ' . $h['name'] . '.';
        });
    }

    /** Pension (pasture puff): done racing and breeding, off to the retirement home. One way. */
    public function pension(int $memberId, int $horseId): string
    {
        return $this->db->transaction(function (Db $db) use ($memberId, $horseId) {
            $h = $this->owned($db, $memberId, $horseId);
            if (!in_array($h['status'], ['active', 'retired'], true)) {
                throw new UserError($h['name'] . ' is already out of the stable.');
            }
            $this->assertNotEntered($db, $h);
            $db->run("UPDATE qp_horses SET status = 'pensioned', stud_fee = NULL WHERE id = ?", [$horseId]);
            return $h['name'] . ' has gone to the retirement home to enjoy life as a pasture puff.';
        });
    }

    /** Send to the TRP: the horse leaves the game for good and a creation credit comes back. */
    public function rehome(int $memberId, int $horseId): string
    {
        return $this->db->transaction(function (Db $db) use ($memberId, $horseId) {
            $h = $this->owned($db, $memberId, $horseId);
            if (!in_array($h['status'], ['active', 'retired'], true)) {
                throw new UserError($h['name'] . ' is already out of the stable.');
            }
            if (!$h['offered_for_sale']) {
                throw new UserError($h['name'] . ' has to be offered first, in a claiming race or at auction, before going to the TRP.');
            }
            $this->assertNotEntered($db, $h);
            $credits = $this->settings->int('trp_credit_refund');
            $db->run("UPDATE qp_horses SET status = 'rehomed', stud_fee = NULL WHERE id = ?", [$horseId]);
            $db->run('UPDATE qp_stables SET creation_credits = creation_credits + ? WHERE member_id = ?', [$credits, $memberId]);
            return $h['name'] . " has gone to the Thoroughbred Rehoming Project. You got $credits creation credit" . ($credits === 1 ? '' : 's') . ' back.';
        });
    }

    private function assertNotEntered(Db $db, array $h): void
    {
        if ($db->value("SELECT 1 FROM qp_entries e JOIN qp_races r ON r.id = e.race_id WHERE e.horse_id = ? AND r.status = 'open'", [$h['id']])) {
            throw new UserError($h['name'] . ' is entered in a race. Scratch first.');
        }
    }
}
