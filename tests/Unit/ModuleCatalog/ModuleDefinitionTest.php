<?php

declare(strict_types=1);

namespace Tests\Unit\ModuleCatalog;

use App\Modules\ModuleCatalog\Domain\Entities\ModuleDefinition;
use App\Modules\ModuleCatalog\Domain\ValueObjects\AllowedScope;
use App\Modules\ModuleCatalog\Domain\ValueObjects\Audience;
use App\Modules\ModuleCatalog\Domain\ValueObjects\ModuleIdentifier;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ModuleDefinitionTest extends TestCase
{
    public function test_restore_keeps_the_module_disabled(): void
    {
        $now = new DateTimeImmutable('2026-07-27T12:00:00+00:00');
        $module = ModuleDefinition::create(
            id: UuidV7::fromString('019fa000-0000-7000-8000-000000000002'),
            identifier: new ModuleIdentifier('sales'),
            name: 'Sales',
            description: 'Sales operations.',
            now: $now,
        );

        $module->softDelete($now);
        $module->restore($now->modify('+1 hour'));

        self::assertSame('disabled', $module->status()->value);
        self::assertFalse($module->isOperational());
    }

    public function test_protocol_metadata_uses_stable_values_and_forbids_wildcard_scope(): void
    {
        self::assertSame('sales-api', (new Audience('sales-api'))->value);
        self::assertSame('sales.orders.read', (new AllowedScope('sales.orders.read'))->value);

        $this->expectException(InvalidArgumentException::class);

        new AllowedScope('*');
    }
}
