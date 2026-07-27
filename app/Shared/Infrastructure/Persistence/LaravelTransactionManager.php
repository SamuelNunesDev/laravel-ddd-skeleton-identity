<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Application\Contracts\TransactionManager;
use Closure;
use Illuminate\Database\DatabaseManager;

final readonly class LaravelTransactionManager implements TransactionManager
{
    public function __construct(private DatabaseManager $database) {}

    public function run(Closure $operation): mixed
    {
        return $this->database->connection()->transaction($operation);
    }
}
