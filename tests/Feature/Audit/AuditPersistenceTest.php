<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Modules\Audit\Application\DTO\AuditEventData;
use App\Modules\Audit\Application\Ports\In\RecordAuditEvent;
use App\Modules\Audit\Domain\Services\SensitiveDataRedactor;
use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Audit\Domain\ValueObjects\AuditOutcome;
use App\Modules\Audit\Domain\ValueObjects\AuditSensitivity;
use App\Modules\Audit\Domain\ValueObjects\AuditTarget;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\RequestId;
use App\Shared\Domain\ValueObjects\TraceId;
use App\Shared\Domain\ValueObjects\UuidV7;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class AuditPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_appends_a_redacted_event_with_actor_target_and_correlation(): void
    {
        $eventId = $this->recorder()->record($this->eventData());

        $stored = DB::table('audit_events')->where('id', $eventId->toString())->first();

        self::assertNotNull($stored);
        self::assertSame('identity', $stored->actor_type);
        self::assertSame('identity', $stored->target_type);
        self::assertSame('018f47a2-4b9d-7cc1-8b7a-112233445566', $stored->request_id);
        self::assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $stored->trace_id);
        self::assertSame('sensitive', $stored->sensitivity);

        $before = $this->decodeJson($stored->before_values);
        $after = $this->decodeJson($stored->after_values);
        $metadata = $this->decodeJson($stored->metadata);

        self::assertSame(SensitiveDataRedactor::REDACTED, $before['password']);
        self::assertSame(SensitiveDataRedactor::REDACTED, $after['access_token']);
        self::assertSame(SensitiveDataRedactor::REDACTED, $metadata['totp_code']);
        self::assertSame('person@example.test', $after['email']);
    }

    public function test_database_rejects_updates_to_audit_events(): void
    {
        $eventId = $this->recorder()->record($this->eventData());

        $this->expectException(QueryException::class);

        DB::table('audit_events')
            ->where('id', $eventId->toString())
            ->update(['action' => 'identity.tampered']);
    }

    public function test_database_rejects_deletes_from_audit_events(): void
    {
        $eventId = $this->recorder()->record($this->eventData());

        $this->expectException(QueryException::class);

        DB::table('audit_events')
            ->where('id', $eventId->toString())
            ->delete();
    }

    public function test_sensitive_operation_and_audit_event_roll_back_atomically(): void
    {
        Schema::create('audit_atomicity_probes', function (Blueprint $table): void {
            $table->id();
            $table->string('value');
        });

        try {
            $this->transactionManager()->run(function (): void {
                DB::table('audit_atomicity_probes')->insert(['value' => 'must-roll-back']);
                $this->recorder()->record($this->eventData());

                throw new RuntimeException('Force rollback.');
            });

            self::fail('The operation should have thrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('Force rollback.', $exception->getMessage());
        }

        self::assertSame(0, DB::table('audit_atomicity_probes')->count());
        self::assertSame(0, DB::table('audit_events')->count());
    }

    private function recorder(): RecordAuditEvent
    {
        return $this->app->make(RecordAuditEvent::class);
    }

    private function transactionManager(): TransactionManager
    {
        return $this->app->make(TransactionManager::class);
    }

    private function eventData(): AuditEventData
    {
        $identityId = UuidV7::fromString('018f47a2-4b9d-7cc1-8b7a-112233445577');

        return new AuditEventData(
            action: 'identity.updated',
            actor: AuditActor::identity($identityId),
            outcome: AuditOutcome::Succeeded,
            sensitivity: AuditSensitivity::Sensitive,
            correlation: new CorrelationContext(
                requestId: RequestId::fromString('018f47a2-4b9d-7cc1-8b7a-112233445566'),
                traceId: TraceId::fromString('4bf92f3577b34da6a3ce929d0e0e4736'),
            ),
            target: new AuditTarget('identity', $identityId),
            beforeValues: ['password' => 'old-password'],
            afterValues: [
                'email' => 'person@example.test',
                'access_token' => 'full-token',
            ],
            metadata: ['totp_code' => '123456'],
            ipAddress: '127.0.0.1',
            sessionId: 'session-123',
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodeJson(mixed $value): array
    {
        self::assertIsString($value);

        /** @var array<array-key, mixed> $decoded */
        $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
