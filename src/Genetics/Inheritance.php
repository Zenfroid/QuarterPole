<?php
declare(strict_types=1);

namespace QuarterPole\Genetics;

// Mendelian inheritance of the color loci: each parent passes one allele per locus.
final class Inheritance
{
    /** Splits a stored genotype into its two alleles, e.g. 'Ee' => ['E','e'], 'A/At' => ['A','At']. */
    public static function alleles(string $locus, string $g): array
    {
        return match ($locus) {
            'gene_a', 'gene_cr', 'gene_w' => explode('/', $g),
            default => self::twoChar($g),
        };
    }

    /** One allele from each parent per locus. Returns the foal genotype (may be lethal). */
    public static function cross(array $sire, array $dam): array
    {
        $foal = [];
        foreach (['gene_e', 'gene_a', 'gene_g', 'gene_cr', 'gene_o', 'gene_w'] as $locus) {
            $s = self::alleles($locus, $sire[$locus]);
            $d = self::alleles($locus, $dam[$locus]);
            $foal[$locus] = self::format($locus, $s[mt_rand(0, 1)], $d[mt_rand(0, 1)]);
        }
        return $foal;
    }

    /** Homozygous Frame Overo, or two lethal dominant whites (W5/W5, W5/W22, W22/W22): the foal is lost. */
    public static function isLethal(array $g): bool
    {
        return $g['gene_o'] === 'OO' || Color::whiteLethal(explode('/', $g['gene_w']));
    }

    /** Chance (0 to 1) that a cross of these two genotypes is lethal. */
    public static function lethalChance(array $sire, array $dam): float
    {
        $p = 1.0;  // chance the foal survives every lethal locus
        // Frame Overo carriers are always heterozygous (homozygotes die), so each passes it half the time.
        if (self::carries($sire['gene_o']) && self::carries($dam['gene_o'])) {
            $p *= 0.75;
        }
        // Dominant white: count the allele pairs a foal can get that are lethal.
        $lethal = 0;
        foreach (explode('/', $sire['gene_w']) as $s) {
            foreach (explode('/', $dam['gene_w']) as $d) {
                $lethal += (int) Color::whiteLethal([$s, $d]);
            }
        }
        $p *= 1 - $lethal / 4;
        return 1 - $p;
    }

    private static function carries(string $g): bool
    {
        return $g !== 'nn';
    }

    private static function twoChar(string $g): array
    {
        return [$g[0], $g[1]];
    }

    private static function format(string $locus, string $x, string $y): string
    {
        switch ($locus) {
            case 'gene_a':
                $rank = ['A' => 0, 'At' => 1, 'a' => 2];
                return $rank[$x] <= $rank[$y] ? "$x/$y" : "$y/$x";
            case 'gene_cr':
                return $x === 'n' ? "$y/$x" : "$x/$y";
            case 'gene_w':
                return Color::whitePair($x, $y);
            default:
                // Dominant/variant allele first: 'Ee', 'Gg', 'On', 'Wn'.
                $isVariant = fn (string $a) => $a !== 'n' && ctype_upper($a);
                return $isVariant($x) || !$isVariant($y) ? $x . $y : $y . $x;
        }
    }
}
