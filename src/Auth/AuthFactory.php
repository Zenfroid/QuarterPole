<?php
declare(strict_types=1);

namespace QuarterPole\Auth;

final class AuthFactory
{
    public static function make(array $config): Auth
    {
        return match ($config['auth'] ?? 'dev') {
            'smf'   => new SmfAuth($config['smf_ssi_path']),
            'dev'   => new DevAuth(),
            default => throw new \RuntimeException('Unknown auth mode: ' . $config['auth']),
        };
    }
}
