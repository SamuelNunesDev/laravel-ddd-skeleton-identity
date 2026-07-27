<?php

declare(strict_types=1);

namespace App\Shared\Application\Contracts;

use Closure;

interface TransactionManager
{
    /**
     * @template T
     *
     * @param  Closure(): T  $operation
     * @return T
     */
    public function run(Closure $operation): mixed;
}
