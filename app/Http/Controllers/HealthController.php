<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Throwable;

final class HealthController
{
    /**
     * Invoked by the named health.live route.
     *
     * @psalm-suppress PossiblyUnusedMethod Laravel resolves controller methods at runtime.
     */
    public function live(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    /**
     * Invoked by the named health.ready route.
     *
     * @psalm-suppress PossiblyUnusedMethod Laravel resolves controller methods at runtime.
     */
    public function ready(): JsonResponse
    {
        try {
            if (App::environment() !== 'testing') {
                $this->assertPostgresAcceptsTcpConnections();
            }

            DB::purge();
            DB::select('SELECT 1');
            Redis::connection()->command('ping');
        } catch (Throwable) {
            return response()->json(['status' => 'unavailable'], 503);
        }

        return response()->json(['status' => 'ready']);
    }

    private function assertPostgresAcceptsTcpConnections(): void
    {
        $host = config('database.connections.pgsql.host');
        $port = (int) config('database.connections.pgsql.port');
        $socket = @stream_socket_client(
            address: sprintf('tcp://%s:%d', $host, $port),
            timeout: 2.0,
        );

        if ($socket === false) {
            throw new RuntimeException('PostgreSQL is unavailable.');
        }

        fclose($socket);
    }
}
