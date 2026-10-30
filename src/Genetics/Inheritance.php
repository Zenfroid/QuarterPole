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
            'gene_a', 'gene_cr' => explode('/', $g),
            default => self::twoChar($g),
        };
    }

    /** One allele from each parent per locus. Returns the foal genotype (may be lethal). */
    public static function cross(array $sire, array $dam): array
    {
        $foal = [];
        foreach (['gene_e', 'gene_a', 'gene_g', 'gene_cr', 'gene_o', 'gene_w5', 'gene_w20', 'gene_w22'] as $locus) {
            $s = self::alleles($locus, $sire[$locus]);
            $d = self::alleles($locus, $dam[$locus]);
            $foal[$locus] = self::format($locus, $s[mt_rand(0, 1)], $d[mt_rand(0, 1)]);
        }
        return $foal;
    }

    /** Homozygous Frame Overo, W5 or W22: the foal is born dead. */
    public static function isLethal(array $g): bool
    {
        return $g['gene_o'] === 'OO'
            || $g['gene_w5'] === 'WW'
            || $g['gene_w22'] === 'WW';
    }

    /** Chance (0 to 1) that a cross of these two genotypes is lethal. */
    public static function lethalChance(array $sire, array $dam): float
    {
        $p = 1.0;  // chance the foal survives every lethal locus
        foreach (['gene_o', 'gene_w5', 'gene_w22'] as $locus) {
            $sHas = self::carries($sire[$locus]);
            $dHas = self::carries($dam[$locus]);
            // Carriers are always heterozygous (homozygotes die), so each passes it half the time.
            if ($sHas && $dHas) {
                $p *= 0.75;
            }
        }
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
            default:
                // Dominant/variant allele first: 'Ee', 'Gg', 'On', 'Wn'.
                $isVariant = fn (string $a) => $a !== 'n' && ctype_upper($a);
                return $isVariant($x) || !$isVariant($y) ? $x . $y : $y . $x;
        }
    }
}
