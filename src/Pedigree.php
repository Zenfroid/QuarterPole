<?php
declare(strict_types=1);

namespace QuarterPole;

// Pedigree lookups and the coefficient of inbreeding (COI) over 5 generations.
final class Pedigree
{
    public const GENERATIONS = 5;

    public function __construct(private Db $db) {}

    /**
     * Every ancestor of $horseId up to $maxDepth generations back, as id => list of depths
     * (1 = parent). A horse that appears twice in the pedigree has two depths.
     */
    public function ancestors(int $horseId, int $maxDepth): array
    {
        $found = [];
        $level = [$horseId];
        for ($depth = 1; $depth <= $maxDepth && $level; $depth++) {
            $rows = $this->db->all(
                'SELECT id, sire_id, dam_id FROM qp_horses WHERE id IN (' . implode(',', array_map('intval', $level)) . ')'
            );
            $byId = array_column($rows, null, 'id');
            $next = [];
            // Walk $level with duplicates kept, so a horse reached by two paths counts twice.
            foreach ($level as $id) {
                foreach (['sire_id', 'dam_id'] as $col) {
                    $parent = $byId[$id][$col] ?? null;
                    if ($parent) {
                        $found[(int) $parent][] = $depth;
                        $next[] = (int) $parent;
                    }
                }
            }
            $level = $next;
        }
        return $found;
    }

    /**
     * Wright's COI for a foal of $sireId x $damId, counting ancestors within 5 generations of the foal.
     * Every (path from sire, path from dam) pair through a common ancestor A adds 0.5^(n1+n2+1) x (1 + F_A).
     * This path-pair count is the usual approximation; it can slightly overcount when two paths
     * share a horse other than A, which is rare inside 5 generations.
     */
    public function coi(int $sireId, int $damId): float
    {
        $depth = self::GENERATIONS - 1;  // generations above each parent
        $fromSire = $this->ancestors($sireId, $depth);
        $fromDam  = $this->ancestors($damId, $depth);
        $fromSire[$sireId][] = 0;  // a parent can itself be the common ancestor
        $fromDam[$damId][]   = 0;

        $common = array_intersect_key($fromSire, $fromDam);
        if (!$common) {
            return 0.0;
        }
        $fa = $this->db->all(
            'SELECT id, coi FROM qp_horses WHERE id IN (' . implode(',', array_keys($common)) . ')'
        );
        $fa = array_column($fa, 'coi', 'id');

        $coi = 0.0;
        foreach ($common as $id => $sireDepths) {
            foreach ($sireDepths as $n1) {
                foreach ($fromDam[$id] as $n2) {
                    $coi += 0.5 ** ($n1 + $n2 + 1) * (1 + (float) ($fa[$id] ?? 0));
                }
            }
        }
        return min(1.0, $coi);
    }

    /** The parents and grandparents for the horse page. */
    public function tree(array $horse, int $generations = 3): array
    {
        $node = fn (?int $id) => $id ? $this->db->one('SELECT id, name, sire_id, dam_id, color_name FROM qp_horses WHERE id = ?', [$id]) : null;
        $build = function (?array $h, int $gen) use (&$build, $node, $generations): ?array {
            if (!$h) {
                return null;
            }
            if ($gen < $generations) {
                $h['sire'] = $build($node($h['sire_id'] ? (int) $h['sire_id'] : null), $gen + 1);
                $h['dam']  = $build($node($h['dam_id'] ? (int) $h['dam_id'] : null), $gen + 1);
            }
            return $h;
        };
        return $build($horse, 0);
    }
}
