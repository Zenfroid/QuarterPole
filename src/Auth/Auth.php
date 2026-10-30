<?php
declare(strict_types=1);

namespace QuarterPole\Auth;

interface Auth
{
    /** The logged-in member, or null for a guest. */
    public function member(): ?Member;

    /** Where to send a guest to log in. */
    public function loginUrl(): string;

    public function logoutUrl(): string;
}
