<?php
declare(strict_types=1);

namespace QuarterPole\Auth;

// Test login: pick any member id and name. Never use on a live site.
final class DevAuth implements Auth
{
    public function member(): ?Member
    {
        $m = $_SESSION['dev_member'] ?? null;
        return $m ? new Member((int) $m['id'], $m['name'], (bool) $m['admin']) : null;
    }

    public function login(int $id, string $name, bool $admin): void
    {
        session_regenerate_id(true);
        $_SESSION['dev_member'] = ['id' => $id, 'name' => $name, 'admin' => $admin];
    }

    public function logout(): void
    {
        unset($_SESSION['dev_member']);
    }

    public function loginUrl(): string
    {
        return '?page=dev-login';
    }

    public function logoutUrl(): string
    {
        return '?page=dev-logout';
    }
}
