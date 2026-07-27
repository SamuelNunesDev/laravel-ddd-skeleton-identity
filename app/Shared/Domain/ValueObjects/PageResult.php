<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * @template T
 */
final readonly class PageResult
{
    /**
     * @param  list<T>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public PageRequest $request,
    ) {
        if ($this->total < 0) {
            throw new InvalidArgumentException('Pagination total cannot be negative.');
        }

        if (count($this->items) > $this->request->perPage) {
            throw new InvalidArgumentException('A page cannot contain more items than requested.');
        }
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->request->perPage));
    }

    public function hasNextPage(): bool
    {
        return $this->request->page < $this->lastPage();
    }
}
