<?php
declare(strict_types=1);

namespace QuarterPole;

// Text pages (welcome, rules, guide) that admins edit on the admin panel.
// Bodies are plain text with a few simple marks, turned into safe HTML here:
//   ## Heading   ### Smaller heading   - bullet   **bold**   [link text](?page=races)
// A blank line starts a new paragraph.
final class Pages
{
    public const SLUGS = ['welcome' => 'Welcome (home page)', 'rules' => 'Rules', 'guide' => 'Guide'];

    public static function find(Db $db, string $slug): ?array
    {
        return $db->one('SELECT * FROM qp_pages WHERE slug = ?', [$slug]);
    }

    public static function save(Db $db, string $slug, string $title, string $body): void
    {
        if (!isset(self::SLUGS[$slug])) {
            throw new UserError('Unknown page.');
        }
        $title = trim(preg_replace('/\s+/', ' ', $title));
        if ($title === '' || mb_strlen($title) > 100) {
            throw new UserError('Give the page a title of up to 100 characters.');
        }
        $body = str_replace("\r\n", "\n", $body);
        if (strlen($body) > 60000) {
            throw new UserError('That page is too long.');
        }
        $db->run('INSERT INTO qp_pages (slug, title, body) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body)',
            [$slug, $title, $body]);
    }

    /** Turns a page body into HTML. Everything is escaped first, so no HTML typed in the body gets through. */
    public static function html(string $body): string
    {
        $out = [];
        $para = [];
        $list = [];
        $flush = function () use (&$out, &$para, &$list) {
            if ($para) {
                $out[] = '<p>' . implode('<br>', $para) . '</p>';
                $para = [];
            }
            if ($list) {
                $out[] = '<ul>' . implode('', array_map(fn ($li) => "<li>$li</li>", $list)) . '</ul>';
                $list = [];
            }
        };
        foreach (explode("\n", str_replace("\r\n", "\n", $body)) as $line) {
            $line = rtrim($line);
            if ($line === '') {
                $flush();
            } elseif (preg_match('/^(#{2,3})\s+(.*)$/', $line, $m)) {
                $flush();
                $tag = strlen($m[1]) === 2 ? 'h2' : 'h3';
                $out[] = "<$tag>" . self::inline($m[2]) . "</$tag>";
            } elseif (preg_match('/^[-*]\s+(.*)$/', $line, $m)) {
                if ($para) {
                    $flush();
                }
                $list[] = self::inline($m[1]);
            } else {
                if ($list) {
                    $flush();
                }
                $para[] = self::inline($line);
            }
        }
        $flush();
        return implode("\n", $out);
    }

    private static function inline(string $text): string
    {
        $s = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s);
        // Links only to game pages (?page=...), the forum, or http(s) addresses.
        return preg_replace_callback('/\[([^\]]+)\]\(((?:\?|\/|https?:\/\/)[^)\s]*)\)/', fn ($m) => '<a href="' . $m[2] . '">' . $m[1] . '</a>', $s);
    }
}
