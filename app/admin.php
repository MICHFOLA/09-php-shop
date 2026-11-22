<?php
declare(strict_types=1);

function admin_password_hash(): ?string
{
    $hash = getenv('ADMIN_PASSWORD_HASH');
    if ($hash === false || $hash === '' || password_get_info($hash)['algo'] === null) {
        return null;
    }

    return $hash;
}

function admin_is_authenticated(): bool
{
    return ($_SESSION['admin_authenticated'] ?? false) === true;
}

function admin_require_auth(): void
{
    if (!admin_is_authenticated()) {
        redirect('login.php');
    }
}

function admin_status_transitions(string $status): array
{
    return match ($status) {
        'pending' => ['paid', 'cancelled'],
        'paid' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        default => [],
    };
}

function admin_valid_date(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}
