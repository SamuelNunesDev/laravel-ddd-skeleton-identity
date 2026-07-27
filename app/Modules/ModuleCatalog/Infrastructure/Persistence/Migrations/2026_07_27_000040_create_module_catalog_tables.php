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
        Schema::create('modules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('identifier', 63)->unique();
            $table->string('name', 160);
            $table->string('description', 2000);
            $table->string('status', 32);
            $table->timestampTz('disabled_at', 6)->nullable();
            $table->timestampTz('deleted_at', 6)->nullable();
            $table->timestampTz('restored_at', 6)->nullable();
            $table->timestampTz('created_at', 6);
            $table->timestampTz('updated_at', 6);

            $table->index(['status', 'deleted_at']);
        });

        Schema::create('module_audiences', function (Blueprint $table): void {
            $table->uuid('module_id');
            $table->string('audience', 191);
            $table->boolean('active');
            $table->timestampTz('retired_at', 6)->nullable();
            $table->timestampTz('created_at', 6);
            $table->timestampTz('updated_at', 6);

            $table->primary(['module_id', 'audience']);
            $table->foreign('module_id')->references('id')->on('modules')->restrictOnDelete();
            $table->index(['audience', 'active']);
        });

        Schema::create('module_allowed_scopes', function (Blueprint $table): void {
            $table->uuid('module_id');
            $table->string('scope', 128);
            $table->boolean('active');
            $table->timestampTz('retired_at', 6)->nullable();
            $table->timestampTz('created_at', 6);
            $table->timestampTz('updated_at', 6);

            $table->primary(['module_id', 'scope']);
            $table->foreign('module_id')->references('id')->on('modules')->restrictOnDelete();
            $table->index(['scope', 'active']);
        });

        Schema::create('organization_modules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('module_id');
            $table->string('status', 32);
            $table->timestampTz('enabled_at', 6);
            $table->timestampTz('disabled_at', 6)->nullable();
            $table->timestampTz('created_at', 6);
            $table->timestampTz('updated_at', 6);

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('module_id')->references('id')->on('modules')->restrictOnDelete();
            $table->index(['organization_id', 'status', 'module_id']);
            $table->index(['module_id', 'status', 'organization_id']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX organization_modules_active_unique '.
            'ON organization_modules (organization_id, module_id) '.
            "WHERE status = 'enabled' AND disabled_at IS NULL",
        );

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
            DB::unprepared('DROP TRIGGER IF EXISTS organization_modules_prevent_hard_delete ON organization_modules');
            DB::unprepared('DROP TRIGGER IF EXISTS module_allowed_scopes_prevent_hard_delete ON module_allowed_scopes');
            DB::unprepared('DROP TRIGGER IF EXISTS module_audiences_prevent_hard_delete ON module_audiences');
            DB::unprepared('DROP TRIGGER IF EXISTS modules_prevent_hard_delete ON modules');
        }

        Schema::dropIfExists('organization_modules');
        Schema::dropIfExists('module_allowed_scopes');
        Schema::dropIfExists('module_audiences');
        Schema::dropIfExists('modules');

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_m3_module_catalog_hard_delete()');
        }
    }

    private function protectPostgres(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE modules
                ADD CONSTRAINT modules_identifier_check
                    CHECK (identifier ~ '^[a-z][a-z0-9]*(-[a-z0-9]+)*$'),
                ADD CONSTRAINT modules_status_check
                    CHECK (status IN ('active', 'disabled'))
            SQL
        );
        DB::unprepared(<<<'SQL'
            ALTER TABLE module_audiences
                ADD CONSTRAINT module_audiences_value_check
                    CHECK (audience ~ '^[A-Za-z0-9][A-Za-z0-9._:/-]{0,190}$'),
                ADD CONSTRAINT module_audiences_lifecycle_check
                    CHECK (
                        (active = TRUE AND retired_at IS NULL)
                        OR (active = FALSE AND retired_at IS NOT NULL)
                    )
            SQL
        );
        DB::unprepared(<<<'SQL'
            ALTER TABLE module_allowed_scopes
                ADD CONSTRAINT module_allowed_scopes_value_check
                    CHECK (scope <> '*' AND scope ~ '^[a-z][a-z0-9._:-]{0,127}$'),
                ADD CONSTRAINT module_allowed_scopes_lifecycle_check
                    CHECK (
                        (active = TRUE AND retired_at IS NULL)
                        OR (active = FALSE AND retired_at IS NOT NULL)
                    )
            SQL
        );
        DB::unprepared(<<<'SQL'
            ALTER TABLE organization_modules
                ADD CONSTRAINT organization_modules_status_check
                    CHECK (status IN ('enabled', 'disabled')),
                ADD CONSTRAINT organization_modules_validity_check
                    CHECK (
                        (status = 'enabled' AND disabled_at IS NULL)
                        OR (status = 'disabled' AND disabled_at IS NOT NULL AND disabled_at >= enabled_at)
                    )
            SQL
        );
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_m3_module_catalog_hard_delete()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RAISE EXCEPTION '% cannot be hard deleted', TG_TABLE_NAME;
            END;
            $$
            SQL
        );

        foreach (['modules', 'module_audiences', 'module_allowed_scopes', 'organization_modules'] as $table) {
            DB::unprepared(sprintf(
                'CREATE TRIGGER %1$s_prevent_hard_delete '.
                'BEFORE DELETE ON %1$s FOR EACH ROW '.
                'EXECUTE FUNCTION prevent_m3_module_catalog_hard_delete()',
                $table,
            ));
        }
    }

    private function protectSqlite(): void
    {
        foreach (['modules', 'module_audiences', 'module_allowed_scopes', 'organization_modules'] as $table) {
            DB::unprepared(sprintf(
                'CREATE TRIGGER %1$s_prevent_hard_delete '.
                'BEFORE DELETE ON %1$s BEGIN '.
                "SELECT RAISE(ABORT, '%1\$s cannot be hard deleted'); END",
                $table,
            ));
        }
    }
};
