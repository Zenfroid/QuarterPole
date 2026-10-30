<?php
declare(strict_types=1);

// Small global helpers used by the templates.

/** HTML-escape. */
function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(int|string $v): string
{
    return number_format((int) $v) . ' Cash';
}

/** 1st, 2nd, 3rd, 4th ... */
function ordinal(int $n): string
{
    $suffix = (in_array($n % 100, [11, 12, 13], true)) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');
    return $n . $suffix;
}
