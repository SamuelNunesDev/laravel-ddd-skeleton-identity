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
        Schema::create('installations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('singleton_key')->default(1)->unique();
            $table->uuid('owner_identity_id');
            $table->string('state', 32);
            $table->string('display_name', 120);
            $table->string('short_name', 60)->nullable();
            $table->string('legal_name', 180)->nullable();
            $table->string('institutional_description', 2000)->nullable();
            $table->string('logo_url', 2048)->nullable();
            $table->string('logo_dark_url', 2048)->nullable();
            $table->string('favicon_url', 2048)->nullable();
            $table->char('primary_color', 7)->nullable();
            $table->char('secondary_color', 7)->nullable();
            $table->char('accent_color', 7)->nullable();
            $table->string('locale', 16);
            $table->string('timezone', 64);
            $table->string('sender_name', 120)->nullable();
            $table->string('sender_email', 254)->nullable();
            $table->string('support_email', 254)->nullable();
            $table->string('support_url', 2048)->nullable();
            $table->string('terms_url', 2048)->nullable();
            $table->string('privacy_policy_url', 2048)->nullable();
            $table->timestampTz('created_at', 6);
            $table->timestampTz('updated_at', 6);

            $table->foreign('owner_identity_id')
                ->references('id')
                ->on('identities')
                ->restrictOnDelete();
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared(<<<'SQL'
                ALTER TABLE installations
                    ADD CONSTRAINT installations_singleton_check
                        CHECK (singleton_key = 1),
                    ADD CONSTRAINT installations_state_check
                        CHECK (state IN ('pending_mfa', 'active'))
                SQL
            );
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION prevent_installations_hard_delete()
                RETURNS trigger
                LANGUAGE plpgsql
                AS $$
                BEGIN
                    RAISE EXCEPTION 'installations cannot be hard deleted';
                END;
                $$
                SQL
            );
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER installations_prevent_hard_delete
                BEFORE DELETE ON installations
                FOR EACH ROW
                EXECUTE FUNCTION prevent_installations_hard_delete()
                SQL
            );
        }

        if ($driver === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER installations_prevent_hard_delete
                BEFORE DELETE ON installations
                BEGIN
                    SELECT RAISE(ABORT, 'installations cannot be hard deleted');
                END
                SQL
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS installations_prevent_hard_delete ON installations');
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_installations_hard_delete()');
        }

        Schema::dropIfExists('installations');
    }
};
