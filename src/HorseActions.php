<?php
declare(strict_types=1);

namespace QuarterPole;

// Owner actions on the horse page: retire to breeding, stud settings, genetic test.
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
            if ($db->value("SELECT 1 FROM qp_entries e JOIN qp_races r ON r.id = e.race_id WHERE e.horse_id = ? AND r.status = 'open'", [$horseId])) {
                throw new UserError($h['name'] . ' is entered in a race. Scratch first, then retire.');
            }
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
}
