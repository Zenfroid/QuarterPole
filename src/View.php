<?php
declare(strict_types=1);

namespace QuarterPole;

final class View
{
    /** Renders templates/$name.php between the site header and footer. */
    public static function render(string $name, array $vars = []): void
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        require QP_ROOT . "/templates/$name.php";
        $content = ob_get_clean();
        require QP_ROOT . '/templates/header.php';
        echo $content;
        require QP_ROOT . '/templates/footer.php';
    }

    public static function redirect(string $to): never
    {
        header('Location: ' . $to);
        exit;
    }

    public static function flash(?string $message = null, string $kind = 'ok'): ?array
    {
        if ($message !== null) {
            $_SESSION['flash'] = ['text' => $message, 'kind' => $kind];
            return null;
        }
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }
}
