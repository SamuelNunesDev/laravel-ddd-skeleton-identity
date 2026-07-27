<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestampTz('occurred_at', 6);
            $table->string('actor_type', 32);
            $table->uuid('actor_id')->nullable();
            $table->string('action');
            $table->string('target_type', 128)->nullable();
            $table->uuid('target_id')->nullable();
            $table->uuid('organization_id')->nullable();
            $table->uuid('module_id')->nullable();
            $table->uuid('request_id');
            $table->char('trace_id', 32);
            $table->string('session_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('outcome', 32);
            $table->string('sensitivity', 32);
            $table->jsonb('before_values')->nullable();
            $table->jsonb('after_values')->nullable();
            $table->jsonb('metadata');

            $table->index(['occurred_at', 'id']);
            $table->index(['organization_id', 'occurred_at']);
            $table->index(['module_id', 'occurred_at']);
            $table->index('request_id');
            $table->index('trace_id');
            $table->index(['actor_type', 'actor_id', 'occurred_at']);
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $this->protectPostgresTable();
        }

        if ($driver === 'sqlite') {
            $this->protectSqliteTable();
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_events_prevent_mutation ON audit_events');
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_audit_events_mutation()');
        }

        Schema::dropIfExists('audit_events');
    }

    private function protectPostgresTable(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_events
                ADD CONSTRAINT audit_events_actor_type_check
                    CHECK (actor_type IN ('identity', 'oauth_client', 'system')),
                ADD CONSTRAINT audit_events_actor_id_check
                    CHECK (
                        (actor_type = 'system' AND actor_id IS NULL)
                        OR (actor_type <> 'system' AND actor_id IS NOT NULL)
                    ),
                ADD CONSTRAINT audit_events_outcome_check
                    CHECK (outcome IN ('succeeded', 'failed', 'denied')),
                ADD CONSTRAINT audit_events_sensitivity_check
                    CHECK (sensitivity IN ('sensitive', 'non_sensitive')),
                ADD CONSTRAINT audit_events_trace_id_check
                    CHECK (trace_id ~ '^[0-9a-f]{32}$' AND trace_id <> '00000000000000000000000000000000')
            SQL
        );

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_audit_events_mutation()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RAISE EXCEPTION 'audit_events are append-only';
            END;
            $$
            SQL
        );

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER audit_events_prevent_mutation
            BEFORE UPDATE OR DELETE ON audit_events
            FOR EACH ROW
            EXECUTE FUNCTION prevent_audit_events_mutation()
            SQL
        );
    }

    private function protectSqliteTable(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER audit_events_prevent_update
            BEFORE UPDATE ON audit_events
            BEGIN
                SELECT RAISE(ABORT, 'audit_events are append-only');
            END
            SQL
        );

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER audit_events_prevent_delete
            BEFORE DELETE ON audit_events
            BEGIN
                SELECT RAISE(ABORT, 'audit_events are append-only');
            END
            SQL
        );
    }
};
