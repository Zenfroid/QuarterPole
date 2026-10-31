<?php
declare(strict_types=1);

namespace QuarterPole;

// The Bank page: sending Cash to another stable, and the stable's Cash history.
final class Bank
{
    // How each kind of Cash movement reads in the history.
    public const KINDS = [
        'start' => 'Starting Cash', 'purse' => 'Purse', 'entry_fee' => 'Entry fee', 'stud_fee_in' => 'Stud fee received',
        'stud_fee_out' => 'Stud fee paid', 'sale' => 'Sale', 'purchase' => 'Purchase', 'claim_in' => 'Horse claimed from you',
        'claim_out' => 'Claim', 'refund' => 'Refund', 'interest' => 'Interest', 'genetic_test' => 'Genetic test',
        'trainer_report' => 'Trainer report', 'admin' => 'Admin', 'transfer_in' => 'Received', 'transfer_out' => 'Sent',
    ];

    public function __construct(private Db $db) {}

    /** Sends Cash from one stable to another, named by its stable name. Returns the message to show. */
    public function send(int $fromId, string $toName, string $amount, string $note): string
    {
        $to = $this->db->one('SELECT * FROM qp_stables WHERE stable_name = ?', [trim($toName)]);
        if (!$to) {
            throw new UserError('There is no stable called "' . trim($toName) . '". Check the spelling.');
        }
        if ((int) $to['member_id'] === $fromId) {
            throw new UserError("You can't send Cash to your own stable.");
        }
        $amount = str_replace([',', ' '], '', trim($amount));
        if (!preg_match('/^\d{1,10}$/', $amount) || (int) $amount < 1) {
            throw new UserError('Enter the amount as a whole number of Cash, e.g. 25000.');
        }
        $amount = (int) $amount;
        $note = trim(preg_replace('/\s+/', ' ', $note));
        if (mb_strlen($note) > 120) {
            throw new UserError('Keep the note to 120 characters.');
        }
        $fromName = (string) $this->db->value('SELECT stable_name FROM qp_stables WHERE member_id = ?', [$fromId]);
        $suffix = $note === '' ? '' : ": $note";
        $this->db->transaction(function (Db $db) use ($fromId, $to, $amount, $fromName, $suffix) {
            $ledger = new Ledger($db);
            $ledger->move($fromId, -$amount, 'transfer_out', 'qp_stables', (int) $to['member_id'], 'To ' . $to['stable_name'] . $suffix);
            $ledger->move((int) $to['member_id'], $amount, 'transfer_in', 'qp_stables', $fromId, 'From ' . $fromName . $suffix);
        });
        return 'Sent ' . money($amount) . ' to ' . $to['stable_name'] . '.';
    }

    /** The stable's latest Cash movements, newest first. */
    public function history(int $memberId, int $limit = 50): array
    {
        return $this->db->all(
            'SELECT * FROM qp_transactions WHERE member_id = ? ORDER BY id DESC LIMIT ' . max(1, $limit),
            [$memberId]
        );
    }

    /** Every other stable's name, for the "send to" suggestions. */
    public function otherStables(int $memberId): array
    {
        return $this->db->all('SELECT stable_name FROM qp_stables WHERE member_id <> ? ORDER BY stable_name', [$memberId]);
    }
}
