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
        Schema::create('identities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('email', 254);
            $table->string('email_normalized', 254);
            $table->string('display_name', 160);
            $table->string('status', 32);
            $table->boolean('must_change_password');
            $table->unsignedBigInteger('authorization_version')->default(1);
            $table->timestampTz('disabled_at', 6)->nullable();
            $table->timestampTz('deleted_at', 6)->nullable();
            $table->timestampTz('restored_at', 6)->nullable();
            $table->timestampTz('created_at', 6);
            $table->timestampTz('updated_at', 6);

            $table->index(['status', 'deleted_at']);
            $table->index('created_at');
        });

        DB::statement(
            'CREATE UNIQUE INDEX identities_email_normalized_active_unique '.
            'ON identities (email_normalized) WHERE deleted_at IS NULL',
        );

        Schema::create('identity_credentials', function (Blueprint $table): void {
            $table->uuid('identity_id')->primary();
            $table->string('password_hash');
            $table->timestampTz('temporary_expires_at', 6)->nullable();
            $table->timestampTz('changed_at', 6)->nullable();
            $table->timestampTz('created_at', 6);
            $table->timestampTz('updated_at', 6);

            $table->foreign('identity_id')
                ->references('id')
                ->on('identities')
                ->restrictOnDelete();
            $table->index('temporary_expires_at');
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared(<<<'SQL'
                ALTER TABLE identities
                    ADD CONSTRAINT identities_status_check
                        CHECK (status IN ('active', 'disabled')),
                    ADD CONSTRAINT identities_authorization_version_check
                        CHECK (authorization_version > 0)
                SQL
            );
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION prevent_identities_hard_delete()
                RETURNS trigger
                LANGUAGE plpgsql
                AS $$
                BEGIN
                    RAISE EXCEPTION 'identities cannot be hard deleted';
                END;
                $$
                SQL
            );
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER identities_prevent_hard_delete
                BEFORE DELETE ON identities
                FOR EACH ROW
                EXECUTE FUNCTION prevent_identities_hard_delete()
                SQL
            );
        }

        if ($driver === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER identities_prevent_hard_delete
                BEFORE DELETE ON identities
                BEGIN
                    SELECT RAISE(ABORT, 'identities cannot be hard deleted');
                END
                SQL
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS identities_prevent_hard_delete ON identities');
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_identities_hard_delete()');
        }

        Schema::dropIfExists('identity_credentials');
        Schema::dropIfExists('identities');
    }
};
