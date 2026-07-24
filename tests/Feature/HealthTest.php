<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Tests\TestCase;

final class HealthTest extends TestCase
{
    public function test_liveness_does_not_require_external_dependencies(): void
    {
        $this->getJson(route('health.live'))
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_readiness_passes_when_postgres_and_redis_are_available(): void
    {
        DB::shouldReceive('purge')->once();
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT 1')
            ->andReturn([]);

        Redis::shouldReceive('connection->command')
            ->once()
            ->with('ping')
            ->andReturn('PONG');

        $this->getJson(route('health.ready'))
            ->assertOk()
            ->assertExactJson(['status' => 'ready']);
    }

    public function test_readiness_fails_closed_when_postgres_is_unavailable(): void
    {
        DB::shouldReceive('purge')->once();
        DB::shouldReceive('select')
            ->once()
            ->andThrow(new RuntimeException('Database unavailable'));

        Redis::shouldReceive('connection')->never();

        $this->getJson(route('health.ready'))
            ->assertStatus(503)
            ->assertExactJson(['status' => 'unavailable']);
    }

    public function test_readiness_fails_closed_when_redis_is_unavailable(): void
    {
        DB::shouldReceive('purge')->once();
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT 1')
            ->andReturn([]);

        Redis::shouldReceive('connection')
            ->once()
            ->andThrow(new RuntimeException('Redis unavailable'));

        $this->getJson(route('health.ready'))
            ->assertStatus(503)
            ->assertExactJson(['status' => 'unavailable']);
    }
}
