<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Shared\Domain\ValueObjects\UuidV7;
use Tests\TestCase;

final class CorrelationMiddlewareTest extends TestCase
{
    public function test_it_preserves_valid_request_and_trace_identifiers(): void
    {
        $requestId = '018f47a2-4b9d-7cc1-8b7a-112233445566';
        $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

        $this->withHeaders([
            'X-Request-ID' => $requestId,
            'traceparent' => '00-'.$traceId.'-00f067aa0ba902b7-01',
        ])->getJson(route('health.live'))
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertHeader('X-Trace-ID', $traceId);
    }

    public function test_it_replaces_invalid_untrusted_identifiers(): void
    {
        $response = $this->withHeaders([
            'X-Request-ID' => 'invalid-request-id',
            'X-Trace-ID' => 'not-a-trace',
        ])->getJson(route('health.live'));

        $response->assertOk();

        self::assertNotNull(UuidV7::tryFromString((string) $response->headers->get('X-Request-ID')));
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{32}$/D',
            (string) $response->headers->get('X-Trace-ID'),
        );
    }

    public function test_it_rejects_the_forbidden_w3c_traceparent_version(): void
    {
        $providedTraceId = '4bf92f3577b34da6a3ce929d0e0e4736';

        $response = $this->withHeaders([
            'traceparent' => 'ff-'.$providedTraceId.'-00f067aa0ba902b7-01',
        ])->getJson(route('health.live'));

        $response->assertOk();
        self::assertNotSame($providedTraceId, $response->headers->get('X-Trace-ID'));
    }
}
