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
