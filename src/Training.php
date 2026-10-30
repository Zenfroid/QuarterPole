<?php
declare(strict_types=1);

namespace QuarterPole;

// The Train button: once per horse per real day. Speeds up development toward peak.
// Training does not use Energy. The stable earns a flat AMI once per real day.
final class Training
{
    public function __construct(private Db $db, private Settings $settings) {}

    public static function canTrain(array $h, string $realToday): bool
    {
        return $h['status'] === 'active' && $h['last_trained_on'] !== $realToday;
    }

    /** @return string message for the player */
    public function train(int $memberId, int $horseId, string $realToday): string
    {
        return $this->db->transaction(function (Db $db) use ($memberId, $horseId, $realToday) {
            $h = $db->one('SELECT * FROM qp_horses WHERE id = ? FOR UPDATE', [$horseId]);
            if (!$h || (int) $h['owner_id'] !== $memberId) {
                throw new UserError('That is not your horse.');
            }
            if ($h['status'] !== 'active') {
                throw new UserError('Only active horses can train.');
            }
            if ($h['last_trained_on'] === $realToday) {
                throw new UserError($h['name'] . ' has already trained today.');
            }
            $db->run(
                'UPDATE qp_horses SET last_trained_on = ?, training_progress = LEAST(100, training_progress + ?) WHERE id = ?',
                [$realToday, $this->settings->float('training_progress_per_session'), $horseId]
            );

            $msg = $h['name'] . ' had a good workout.';
            $lastAmi = $db->value('SELECT last_train_ami_date FROM qp_stables WHERE member_id = ? FOR UPDATE', [$memberId]);
            if ($lastAmi !== $realToday) {
                $db->run('UPDATE qp_stables SET last_train_ami_date = ? WHERE member_id = ?', [$realToday, $memberId]);
                $credits = (new Ami($db, $this->settings))->award($memberId, $this->settings->int('ami_training_daily'), 'training', $horseId);
                $msg .= $credits ? " You earned a creation credit!" : '';
            }
            return $msg;
        });
    }
}
