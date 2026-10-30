<?php
declare(strict_types=1);

namespace QuarterPole;

// Every form posts a token so other sites cannot submit forms as the player.
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf" value="' . self::token() . '">';
    }

    public static function check(): void
    {
        if (!hash_equals(self::token(), (string) ($_POST['csrf'] ?? ''))) {
            http_response_code(400);
            exit('Your session expired. Go back and try again.');
        }
    }
}
