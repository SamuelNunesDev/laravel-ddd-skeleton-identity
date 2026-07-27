<?php

declare(strict_types=1);

namespace App\Shared\Application\Contracts;

use App\Shared\Application\Integration\IntegrationEventMessage;

interface IntegrationEventPublisher
{
    public function publish(IntegrationEventMessage $event): void;
}
