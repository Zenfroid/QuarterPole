<?php
declare(strict_types=1);

namespace QuarterPole\Race;

use QuarterPole\Db;
use QuarterPole\UserError;

// The jockey pool: who rides each entry, and the admin page that edits the pool.
final class Jockeys
{
    public function __construct(private Db $db) {}

    /** A random active jockey who isn't already riding in this race. */
    public static function pick(Db $db, int $raceId): int
    {
        $free = $db->value(
            'SELECT id FROM qp_jockeys WHERE active = 1 AND id NOT IN (SELECT jockey_id FROM qp_entries WHERE race_id = ?)
              ORDER BY RAND() LIMIT 1',
            [$raceId]
        );
        // Only when the pool is smaller than the field does a jockey have to ride twice.
        return (int) ($free ?: $db->value('SELECT id FROM qp_jockeys ORDER BY active DESC, RAND() LIMIT 1'));
    }

    public function all(): array
    {
        return $this->db->all(
            'SELECT j.*, COUNT(e.id) AS rides, SUM(e.finish_position = 1) AS wins
               FROM qp_jockeys j LEFT JOIN qp_entries e ON e.jockey_id = j.id AND e.finish_position IS NOT NULL
              GROUP BY j.id ORDER BY j.active DESC, j.name'
        );
    }

    /**
     * Renames and retires existing jockeys and adds new ones. Jockeys are never deleted,
     * so past race charts keep their names. Returns the number of changes.
     */
    public function save(array $names, array $active, array $add): int
    {
        $clean = fn ($s) => trim(preg_replace('/\s+/', ' ', (string) $s));
        $updates = [];
        foreach ($this->db->all('SELECT * FROM qp_jockeys') as $j) {
            $id = (int) $j['id'];
            $name = array_key_exists($id, $names) ? $clean($names[$id]) : $j['name'];
            if ($name === '' || mb_strlen($name) > 60) {
                throw new UserError('Jockey names need 1 to 60 characters. To stop using a jockey, untick Riding instead.');
            }
            $on = isset($active[$id]) ? 1 : 0;
            if ($name !== $j['name'] || $on !== (int) $j['active']) {
                $updates[] = [$name, $on, $id];
            }
        }
        $inserts = [];
        foreach ($add as $new) {
            $new = $clean($new);
            if ($new === '') {
                continue;
            }
            if (mb_strlen($new) > 60) {
                throw new UserError('Jockey names need 1 to 60 characters.');
            }
            $inserts[] = $new;
        }
        $this->db->transaction(function (Db $db) use ($updates, $inserts) {
            foreach ($updates as $u) {
                $db->run('UPDATE qp_jockeys SET name = ?, active = ? WHERE id = ?', $u);
            }
            foreach ($inserts as $n) {
                $db->run('INSERT INTO qp_jockeys (name) VALUES (?)', [$n]);
            }
        });
        return count($updates) + count($inserts);
    }
}
