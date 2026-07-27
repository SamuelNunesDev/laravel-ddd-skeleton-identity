<?php

declare(strict_types=1);

namespace Tests\Unit\Organization;

use App\Modules\Organization\Domain\ValueObjects\OrganizationIdentifier;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OrganizationIdentifierTest extends TestCase
{
    public function test_it_normalizes_a_kebab_case_identifier(): void
    {
        self::assertSame('north-america', (new OrganizationIdentifier(' North-America '))->value);
    }

    public function test_it_rejects_an_unsafe_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OrganizationIdentifier('../north');
    }
}
