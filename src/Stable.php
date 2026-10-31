<?php
declare(strict_types=1);

namespace QuarterPole;

final class Stable
{
    public function __construct(private Db $db, private Settings $settings) {}

    public function find(int $memberId): ?array
    {
        return $this->db->one('SELECT * FROM qp_stables WHERE member_id = ?', [$memberId]);
    }

    /** First visit: name the stable and receive starting Cash and credits. */
    public function create(int $memberId, string $name): void
    {
        $name = trim($name);
        if (mb_strlen($name) < 3 || mb_strlen($name) > 40) {
            throw new UserError('Stable names must be 3 to 40 characters.');
        }
        if (!preg_match("/^[\\p{L}\\p{N} '&.-]+$/u", $name)) {
            throw new UserError('Stable names can use letters, numbers, spaces and \' & . -');
        }
        if ($this->db->value('SELECT 1 FROM qp_stables WHERE stable_name = ?', [$name])) {
            throw new UserError('That stable name is taken.');
        }
        $credits = $this->settings->int('starting_credits') + $this->settings->int('grand_opening_credits');
        $this->db->transaction(function (Db $db) use ($memberId, $name, $credits) {
            $db->run(
                'INSERT INTO qp_stables (member_id, stable_name, cash, creation_credits) VALUES (?, ?, 0, ?)',
                [$memberId, $name, $credits]
            );
            (new Ledger($db))->move($memberId, $this->settings->int('starting_cash'), 'start', null, null, 'Starting Cash');
        });
    }

    /**
     * One page of a stable's horses for the card view. $status 'active' is the racing string
     * (Dashboard); 'retired' is the breeding barn, stallions first, each with their foal count.
     */
    public function horses(int $memberId, int $page, string $status = 'active', int $perPage = 12): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $total = (int) $this->db->value('SELECT COUNT(*) FROM qp_horses WHERE owner_id = ? AND status = ?', [$memberId, $status]);
        $order = $status === 'retired' ? 'h.sex DESC, h.name' : 'h.name';
        $rows = $this->db->all(
            "SELECT h.*, (SELECT COUNT(*) FROM qp_horses f WHERE f.sire_id = h.id OR f.dam_id = h.id) AS foals
               FROM qp_horses h WHERE h.owner_id = ? AND h.status = ?
              ORDER BY $order LIMIT $perPage OFFSET $offset",
            [$memberId, $status]
        );
        $this->attachEntries($rows);
        return ['rows' => $rows, 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    /** Adds 'entries' to each horse: its races not yet run, soonest first. */
    private function attachEntries(array &$rows): void
    {
        foreach ($rows as &$r) {
            $r['entries'] = [];
        }
        unset($r);
        if (!$rows) {
            return;
        }
        $byId = array_flip(array_column($rows, 'id'));
        $ids = implode(',', array_map('intval', array_keys($byId)));
        $entries = $this->db->all(
            "SELECT e.horse_id, r.id AS race_id, r.name, r.race_level, r.run_on, t.name AS track_name
               FROM qp_entries e JOIN qp_races r ON r.id = e.race_id JOIN qp_tracks t ON t.id = r.track_id
              WHERE e.horse_id IN ($ids) AND r.status IN ('open','closed') ORDER BY r.run_on, r.id"
        );
        foreach ($entries as $en) {
            $rows[$byId[$en['horse_id']]]['entries'][] = $en;
        }
    }
}
