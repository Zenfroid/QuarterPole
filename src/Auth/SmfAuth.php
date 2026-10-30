<?php
declare(strict_types=1);

namespace QuarterPole\Auth;

// Uses the SMF 2.1 forum's login. SSI.php reads SMF's cookie and fills $user_info.
// SMF must have "Enable local storage of cookies" turned off so the cookie reaches the game.
final class SmfAuth implements Auth
{
    private ?Member $member = null;
    private bool $loaded = false;

    public function __construct(private string $ssiPath) {}

    public function member(): ?Member
    {
        if (!$this->loaded) {
            $this->loaded = true;
            if (!is_file($this->ssiPath)) {
                throw new \RuntimeException('SMF SSI.php not found at ' . $this->ssiPath);
            }
            require_once $this->ssiPath;
            global $user_info;
            if (!empty($user_info) && empty($user_info['is_guest'])) {
                $this->member = new Member(
                    (int) $user_info['id'],
                    (string) $user_info['name'],
                    !empty($user_info['is_admin']),
                    (int) ($user_info['posts'] ?? 0),
                );
            }
        }
        return $this->member;
    }

    public function loginUrl(): string
    {
        global $scripturl;
        return ($scripturl ?? '/Forum/index.php') . '?action=login';
    }

    public function logoutUrl(): string
    {
        global $scripturl, $context;
        return ($scripturl ?? '/Forum/index.php') . '?action=logout;' . ($context['session_var'] ?? '') . '=' . ($context['session_id'] ?? '');
    }
}
