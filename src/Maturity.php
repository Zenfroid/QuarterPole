<?php
declare(strict_types=1);

namespace QuarterPole;

// How close a horse is to its peak stats right now.
// Stats are stored as peak values; races and reports use peak x maturity.
final class Maturity
{
    // A 2-year-old with a later peak starts at this share of its peak.
    private const START = 0.75;
    // Share of peak lost per game year once the peak years are over.
    private const DECLINE_PER_YEAR = 0.04;

    /**
     * @param int $age           whole game years
     * @param float $yearFraction 0..1 through the current game year
     */
    public static function factor(array $h, int $age, float $yearFraction, int $peakYears): float
    {
        $peak = (int) $h['peak_age'];
        $exact = $age + $yearFraction;

        if ($exact < $peak) {
            // Grows from START at age 2 to 1.0 at peak age; training closes the gap faster.
            $span = max(0.5, $peak - 2);
            $base = self::START + (1 - self::START) * max(0, $exact - 2) / $span;
            $trained = (int) $h['training_progress'] / 100;
            return min(1.0, $base + (1 - $base) * $trained);
        }
        $pastPeak = $exact - ($peak + $peakYears);
        if ($pastPeak <= 0) {
            return 1.0;
        }
        return max(0.5, 1 - self::DECLINE_PER_YEAR * $pastPeak);
    }

    public static function label(float $f, int $age, int $peakAge): string
    {
        if ($f >= 0.999) {
            return 'At peak';
        }
        return $age < $peakAge ? 'Still developing' : 'Past peak';
    }

    public static function yearFraction(\DateTimeImmutable $today): float
    {
        return ((int) $today->format('z')) / 365;
    }
}
