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
        'Cr'  => 0.05,
        'O'   => 0.02,
        'W5'  => 0.02,
        'W20' => 0.10,
        'W22' => 0.01,
    ];

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
            'gene_w20' => self::pair($pick($f['W20'], 'W', 'n'), $pick($f['W20'], 'W', 'n')),
            // Lethal when doubled, so foundation horses carry at most one copy.
            'gene_o'   => $pick($f['O'],   'On', 'nn'),
            'gene_w5'  => $pick($f['W5'],  'Wn', 'nn'),
            'gene_w22' => $pick($f['W22'], 'Wn', 'nn'),
        ];
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
        $w5     = $g['gene_w5'] !== 'nn';
        $w22    = $g['gene_w22'] !== 'nn';
        $w20    = substr_count($g['gene_w20'], 'W');
        $whites = (int) $w5 + (int) $w22 + (int) ($w20 > 0);

        // Any two different dominant whites (W20 included) give maximum white.
        if ($whites >= 2) {
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
