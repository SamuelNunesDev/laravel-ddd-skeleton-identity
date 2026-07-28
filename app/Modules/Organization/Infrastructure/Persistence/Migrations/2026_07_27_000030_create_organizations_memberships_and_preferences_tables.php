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
        Schema::create('organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('identifier', 63)->unique();
            $table->string('name', 160);
            $table->string('mfa_policy', 16);
            $table->string('status', 32);
            $table->timestampTz('disabled_at', 6)->nullable();
            $table->timestampTz('deleted_at', 6)->nullable();
            $table->timestampTz('restored_at', 6)->nullable();
            $table->timestampTz('created_at', 6);
            $table->timestampTz('updated_at', 6);

            $table->index(['status', 'deleted_at']);
        });

        Schema::create('memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('identity_id');
            $table->uuid('organization_id');
            $table->string('status', 32);
            $table->timestampTz('started_at', 6);
            $table->timestampTz('ended_at', 6)->nullable();
            $table->timestampTz('created_at', 6);
            $table->timestampTz('updated_at', 6);

            $table->foreign('identity_id')->references('id')->on('identities')->restrictOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->index(['organization_id', 'status', 'identity_id']);
            $table->index(['identity_id', 'status', 'organization_id']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX memberships_identity_organization_active_unique '.
            "ON memberships (identity_id, organization_id) WHERE status = 'active' AND ended_at IS NULL",
        );

        Schema::create('identity_preferences', function (Blueprint $table): void {
            $table->uuid('identity_id')->primary();
            $table->uuid('last_organization_id');
            $table->timestampTz('updated_at', 6);

            $table->foreign('identity_id')->references('id')->on('identities')->restrictOnDelete();
            $table->foreign('last_organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->index('last_organization_id');
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $this->protectPostgres();
        }

        if ($driver === 'sqlite') {
            $this->protectSqlite();
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS memberships_prevent_hard_delete ON memberships');
            DB::unprepared('DROP TRIGGER IF EXISTS organizations_prevent_hard_delete ON organizations');
        }

        Schema::dropIfExists('identity_preferences');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('organizations');

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_m3_organization_hard_delete()');
        }
    }

    private function protectPostgres(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE organizations
                ADD CONSTRAINT organizations_identifier_check
                    CHECK (identifier ~ '^[a-z][a-z0-9]*(-[a-z0-9]+)*$'),
                ADD CONSTRAINT organizations_mfa_policy_check
                    CHECK (mfa_policy IN ('required', 'optional')),
                ADD CONSTRAINT organizations_status_check
                    CHECK (status IN ('active', 'disabled'))
            SQL
        );
        DB::unprepared(<<<'SQL'
            ALTER TABLE memberships
                ADD CONSTRAINT memberships_status_check
                    CHECK (status IN ('active', 'ended')),
                ADD CONSTRAINT memberships_validity_check
                    CHECK (
                        (status = 'active' AND ended_at IS NULL)
                        OR (status = 'ended' AND ended_at IS NOT NULL AND ended_at >= started_at)
                    )
            SQL
        );
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_m3_organization_hard_delete()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RAISE EXCEPTION '% cannot be hard deleted', TG_TABLE_NAME;
            END;
            $$
            SQL
        );
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER organizations_prevent_hard_delete
            BEFORE DELETE ON organizations
            FOR EACH ROW
            EXECUTE FUNCTION prevent_m3_organization_hard_delete()
            SQL
        );
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER memberships_prevent_hard_delete
            BEFORE DELETE ON memberships
            FOR EACH ROW
            EXECUTE FUNCTION prevent_m3_organization_hard_delete()
            SQL
        );
    }

    private function protectSqlite(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER organizations_prevent_hard_delete
            BEFORE DELETE ON organizations
            BEGIN
                SELECT RAISE(ABORT, 'organizations cannot be hard deleted');
            END
            SQL
        );
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER memberships_prevent_hard_delete
            BEFORE DELETE ON memberships
            BEGIN
                SELECT RAISE(ABORT, 'memberships cannot be hard deleted');
            END
            SQL
        );
    }
};
