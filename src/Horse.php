<?php
declare(strict_types=1);

namespace QuarterPole;

final class Horse
{
    /** Colt, filly, stallion, mare or gelding, from sex and age (the US cut-off is 5). */
    public static function sexLabel(array $h, int $age): string
    {
        if ($h['sex'] === 'F') {
            return $age < 5 ? 'Filly' : 'Mare';
        }
        if ($h['gelded']) {
            return 'Gelding';
        }
        return $age < 5 ? 'Colt' : 'Stallion';
    }

    public static function validateName(Db $db, string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        // Real Jockey Club rule: 18 characters, spaces and punctuation included.
        if ($name === '' || mb_strlen($name) > 18) {
            throw new UserError('Horse names must be 1 to 18 characters.');
        }
        if (!preg_match("/^[A-Za-z0-9 '.-]+$/", $name)) {
            throw new UserError('Horse names can use letters, numbers, spaces and \' . -');
        }
        if ($db->value('SELECT 1 FROM qp_horses WHERE name = ?', [$name])) {
            throw new UserError('That name is already used by another horse.');
        }
        return $name;
    }

    public static function find(Db $db, int $id): ?array
    {
        return $db->one(
            'SELECT h.*, s.stable_name AS owner_name
               FROM qp_horses h LEFT JOIN qp_stables s ON s.member_id = h.owner_id
              WHERE h.id = ?',
            [$id]
        );
    }
}
