<?php
declare(strict_types=1);

namespace QuarterPole;

// Every Cash change goes through here so qp_transactions always matches qp_stables.cash.
final class Ledger
{
    public function __construct(private Db $db) {}

    /** Call inside a transaction. Throws if the stable cannot afford a negative amount. */
    public function move(int $memberId, int $amount, string $kind, ?string $refTable = null, ?int $refId = null, ?string $note = null): int
    {
        $cash = (int) $this->db->value('SELECT cash FROM qp_stables WHERE member_id = ? FOR UPDATE', [$memberId]);
        $after = $cash + $amount;
        if ($after < 0) {
            throw new UserError('Not enough Cash.');
        }
        $this->db->run('UPDATE qp_stables SET cash = ? WHERE member_id = ?', [$after, $memberId]);
        $this->db->run(
            'INSERT INTO qp_transactions (member_id, amount, balance_after, kind, ref_table, ref_id, note) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$memberId, $amount, $after, $kind, $refTable, $refId, $note]
        );
        return $after;
    }
}
