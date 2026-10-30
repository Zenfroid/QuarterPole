<?php
declare(strict_types=1);

namespace QuarterPole;

// AMI (activity points). Converts to creation credits at the ami_per_credit setting.
final class Ami
{
    public function __construct(private Db $db, private Settings $settings) {}

    /** Call inside a transaction. Returns how many credits the award converted into. */
    public function award(int $memberId, int $amount, string $reason, ?int $refId = null): int
    {
        if ($amount <= 0) {
            return 0;
        }
        $this->db->run(
            'INSERT INTO qp_ami_log (member_id, amount, reason, ref_id) VALUES (?, ?, ?, ?)',
            [$memberId, $amount, $reason, $refId]
        );
        $points  = $amount + (int) $this->db->value('SELECT ami_points FROM qp_stables WHERE member_id = ? FOR UPDATE', [$memberId]);
        $per     = max(1, $this->settings->int('ami_per_credit'));
        $credits = intdiv($points, $per);
        if ($credits > 0) {
            $this->db->run(
                "INSERT INTO qp_ami_log (member_id, amount, reason) VALUES (?, ?, 'credit_conversion')",
                [$memberId, -$credits * $per]
            );
        }
        $this->db->run(
            'UPDATE qp_stables SET ami_points = ?, creation_credits = creation_credits + ? WHERE member_id = ?',
            [$points - $credits * $per, $credits, $memberId]
        );
        return $credits;
    }
}
