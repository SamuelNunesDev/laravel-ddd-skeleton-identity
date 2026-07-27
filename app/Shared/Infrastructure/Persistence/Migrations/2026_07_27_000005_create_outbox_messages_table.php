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
        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_name', 191);
            $table->string('aggregate_type', 127);
            $table->uuid('aggregate_id');
            $table->jsonb('payload');
            $table->timestampTz('occurred_at', 6);
            $table->timestampTz('available_at', 6);
            $table->timestampTz('published_at', 6)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();

            $table->index(['aggregate_type', 'aggregate_id', 'occurred_at']);
            $table->index(['event_name', 'occurred_at']);
        });

        DB::statement(
            'CREATE INDEX outbox_messages_pending_index '.
            'ON outbox_messages (available_at, occurred_at) WHERE published_at IS NULL',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
