<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Security;

use App\Modules\Identity\Application\Ports\Out\PasswordHasher;

final readonly class Argon2IdPasswordHasher implements PasswordHasher
{
    private string $dummyHash;

    public function __construct()
    {
        $this->dummyHash = $this->hash('dummy-password-that-is-never-valid');
    }

    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function consumeDummyVerification(string $password): void
    {
        password_verify($password, $this->dummyHash);
    }
}
