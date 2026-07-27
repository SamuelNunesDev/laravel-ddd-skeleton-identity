<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\Ports\Out;

use App\Modules\Installation\Domain\Entities\Installation;

interface InstallationStore
{
    public function acquireInitializationLock(): void;

    public function find(bool $forUpdate = false): ?Installation;

    public function insert(Installation $installation): void;

    public function update(Installation $installation): void;
}
