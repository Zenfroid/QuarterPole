<?php
declare(strict_types=1);

namespace QuarterPole\Auth;

// The logged-in forum member, whichever login system supplied it.
final class Member
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly bool $isAdmin = false,
        public readonly int $forumPosts = 0,
    ) {}
}
