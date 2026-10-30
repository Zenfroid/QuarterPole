<?php
declare(strict_types=1);

namespace QuarterPole;

// The in-game date. Game year 1 is stored as 2001 (MySQL DATE needs year >= 1000).
final class GameClock
{
    public const YEAR_OFFSET = 2000;

    public function __construct(private Db $db) {}

    public function today(): \DateTimeImmutable
    {
        return new \DateTimeImmutable((string) $this->db->value('SELECT game_date FROM qp_game_clock WHERE id = 1'));
    }

    public function year(): int
    {
        return (int) $this->today()->format('Y') - self::YEAR_OFFSET;
    }

    /** All horses age on game January 1, so age is just the difference in years. */
    public static function ageOn(string $birthGameDate, \DateTimeImmutable $today): int
    {
        return (int) $today->format('Y') - (int) substr($birthGameDate, 0, 4);
    }

    public static function label(\DateTimeImmutable $d): string
    {
        return $d->format('F j') . ', Year ' . ((int) $d->format('Y') - self::YEAR_OFFSET);
    }
}
