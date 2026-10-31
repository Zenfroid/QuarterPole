<?php
declare(strict_types=1);

namespace QuarterPole\Genetics;

// Color genotypes and the color name they produce.
// Genotype strings match the qp_horses gene_* columns.
final class Color
{
    // Chance each allele is the dominant/variant one in foundation stock.
    private const FOUNDATION_FREQ = [
        'E'   => 0.55,  // E vs e
        'A'   => 0.45,  // A, otherwise At or a below
        'At'  => 0.20,
        'G'   => 0.06,
        'Cr'  => 0.01,  // rare in Thoroughbreds: about 1 horse in 50 carries it
        'O'   => 0.02,
        // Dominant white alleles, all at one locus (KIT). Each copy is one of these or n.
        'W20' => 0.10,
        'W5'  => 0.01,
        'W22' => 0.005,
    ];

    // Dominant white alleles in display order. Two copies of W5 or W22 (in any mix) are lethal; W20 is not.
    public const WHITE_ORDER = ['W5' => 0, 'W20' => 1, 'W22' => 2, 'n' => 3];
    public const WHITE_LETHAL = ['W5', 'W22'];

    /** A random, non-lethal genotype for a foundation horse. */
    public static function randomFoundation(): array
    {
        $f = self::FOUNDATION_FREQ;
        $pick = fn (float $p, string $yes, string $no) => self::chance($p) ? $yes : $no;
        $agouti = function () use ($f): string {
            $r = mt_rand() / mt_getrandmax();
            return $r < $f['A'] ? 'A' : ($r < $f['A'] + $f['At'] ? 'At' : 'a');
        };

        $g = [
            'gene_e'   => self::pair($pick($f['E'], 'E', 'e'), $pick($f['E'], 'E', 'e')),
            'gene_a'   => self::agoutiPair($agouti(), $agouti()),
            'gene_g'   => self::pair($pick($f['G'], 'G', 'g'), $pick($f['G'], 'G', 'g')),
            'gene_cr'  => self::slashPair($pick($f['Cr'], 'Cr', 'n'), $pick($f['Cr'], 'Cr', 'n')),
            // Lethal when doubled, so foundation horses carry at most one copy.
            'gene_o'   => $pick($f['O'], 'On', 'nn'),
        ];
        do {
            $w = [self::whiteAllele(), self::whiteAllele()];
        } while (self::whiteLethal($w));
        $g['gene_w'] = self::whitePair($w[0], $w[1]);
        return $g;
    }

    /** Display name, e.g. "Bay", "Grey (Chestnut)", "Palomino Frame Overo". */
    public static function name(array $g): string
    {
        $e  = $g['gene_e'];
        $a  = explode('/', $g['gene_a']);
        $cr = substr_count($g['gene_cr'], 'Cr');

        if ($e === 'ee') {
            $base = ['Chestnut', 'Palomino', 'Cremello'][$cr];
        } elseif (in_array('A', $a, true)) {
            $base = ['Bay', 'Buckskin', 'Perlino'][$cr];
        } elseif (in_array('At', $a, true)) {
            $base = ['Seal Brown', 'Brown Buckskin', 'Smoky Cream'][$cr];
        } else {
            $base = ['Black', 'Smoky Black', 'Smoky Cream'][$cr];
        }

        $hasO   = $g['gene_o'] !== 'nn';
        $w      = explode('/', $g['gene_w']);
        $w5     = in_array('W5', $w, true);
        $w22    = in_array('W22', $w, true);
        $w20    = count(array_keys($w, 'W20', true));

        // Two different dominant whites (e.g. W5/W20) give maximum white.
        if ($w[0] !== 'n' && $w[1] !== 'n' && $w[0] !== $w[1]) {
            $name = 'Maximum White (' . $base . ')';
        } else {
            $marks = [];
            if ($w5)  { $marks[] = 'Sabino'; }
            if ($w22) { $marks[] = 'White Spotted'; }
            if ($hasO) { $marks[] = 'Frame Overo'; }
            $name = $base . ($marks ? ' ' . implode(' ', $marks) : '');
            if ($w20 === 2)     { $name .= ' with lots of white'; }
            elseif ($w20 === 1) { $name .= ' with minimal white'; }
        }

        // Grey shows its base color in brackets.
        if (str_contains($g['gene_g'], 'G')) {
            return 'Grey (' . $name . ')';
        }
        return $name;
    }

    /** "W5/W20", "W20/n", "n/n": dominant white alleles in display order. */
    public static function whitePair(string $x, string $y): string
    {
        return self::WHITE_ORDER[$x] <= self::WHITE_ORDER[$y] ? "$x/$y" : "$y/$x";
    }

    /** True when both dominant white copies are lethal ones (W5/W5, W5/W22, W22/W22). */
    public static function whiteLethal(array $w): bool
    {
        return in_array($w[0], self::WHITE_LETHAL, true) && in_array($w[1], self::WHITE_LETHAL, true);
    }

    private static function whiteAllele(): string
    {
        $r = mt_rand() / mt_getrandmax();
        foreach (['W20', 'W5', 'W22'] as $allele) {
            $r -= self::FOUNDATION_FREQ[$allele];
            if ($r < 0) {
                return $allele;
            }
        }
        return 'n';
    }

    private static function chance(float $p): bool
    {
        return mt_rand() / mt_getrandmax() < $p;
    }

    private static function pair(string $x, string $y): string
    {
        // Dominant (uppercase) allele first: "Ee", never "eE".
        return ctype_upper($x[0]) || !ctype_upper($y[0]) ? $x . $y : $y . $x;
    }

    private static function slashPair(string $x, string $y): string
    {
        return $x === 'n' ? "$y/$x" : "$x/$y";
    }

    private static function agoutiPair(string $x, string $y): string
    {
        $rank = ['A' => 0, 'At' => 1, 'a' => 2];
        return $rank[$x] <= $rank[$y] ? "$x/$y" : "$y/$x";
    }
}
