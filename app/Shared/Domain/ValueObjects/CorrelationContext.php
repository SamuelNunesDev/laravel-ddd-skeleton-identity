<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

final readonly class CorrelationContext
{
    public function __construct(
        public RequestId $requestId,
        public TraceId $traceId,
    ) {}
}
