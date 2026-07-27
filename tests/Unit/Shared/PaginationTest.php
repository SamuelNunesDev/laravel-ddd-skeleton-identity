<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Domain\ValueObjects\PageRequest;
use App\Shared\Domain\ValueObjects\PageResult;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function test_page_request_exposes_a_safe_offset(): void
    {
        $request = new PageRequest(page: 3, perPage: 20);

        self::assertSame(40, $request->offset());
    }

    public function test_page_size_is_bounded(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PageRequest(perPage: 101);
    }

    public function test_page_result_calculates_navigation_without_losing_item_types(): void
    {
        $result = new PageResult(
            items: ['first', 'second'],
            total: 5,
            request: new PageRequest(page: 2, perPage: 2),
        );

        self::assertSame(3, $result->lastPage());
        self::assertTrue($result->hasNextPage());
        self::assertSame(['first', 'second'], $result->items);
    }
}
