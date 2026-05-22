<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event');                      // created, updated, deleted
            $table->morphs('auditable');                  // auditable_type + auditable_id
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('previous_payload')->nullable();      // the state before the event
            $table->json('new_payload')->nullable();      // state after the Event
            $table->string('ip_address', 15)->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
