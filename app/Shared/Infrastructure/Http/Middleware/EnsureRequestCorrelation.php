<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Middleware;

use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\RequestId;
use App\Shared\Domain\ValueObjects\TraceId;
use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureRequestCorrelation
{
    public function __construct(
        private UuidGenerator $uuidGenerator,
        private Container $container,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $context = new CorrelationContext(
            requestId: $this->requestId($request),
            traceId: $this->traceId($request),
        );

        $request->attributes->set(CorrelationContext::class, $context);
        $this->container->instance(CorrelationContext::class, $context);

        $response = $next($request);
        $response->headers->set('X-Request-ID', (string) $context->requestId);
        $response->headers->set('X-Trace-ID', (string) $context->traceId);

        return $response;
    }

    private function requestId(Request $request): RequestId
    {
        $provided = RequestId::tryFromString((string) $request->headers->get('X-Request-ID', ''));

        return $provided ?? new RequestId($this->uuidGenerator->generate());
    }

    private function traceId(Request $request): TraceId
    {
        $provided = TraceId::tryFromString((string) $request->headers->get('X-Trace-ID', ''));

        if ($provided !== null) {
            return $provided;
        }

        $traceparent = strtolower((string) $request->headers->get('traceparent', ''));

        if (preg_match('/^([0-9a-f]{2})-([0-9a-f]{32})-[0-9a-f]{16}-[0-9a-f]{2}$/D', $traceparent, $matches) === 1
            && $matches[1] !== 'ff') {
            return TraceId::tryFromString($matches[2]) ?? TraceId::generate();
        }

        return TraceId::generate();
    }
}
