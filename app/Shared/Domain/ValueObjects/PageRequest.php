<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class PageRequest
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 25,
    ) {
        if ($this->page < 1) {
            throw new InvalidArgumentException('Page must be greater than zero.');
        }

        if ($this->perPage < 1 || $this->perPage > 100) {
            throw new InvalidArgumentException('Items per page must be between 1 and 100.');
        }

        if (($this->page - 1) > intdiv(PHP_INT_MAX, $this->perPage)) {
            throw new InvalidArgumentException('The requested page is too large.');
        }
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
