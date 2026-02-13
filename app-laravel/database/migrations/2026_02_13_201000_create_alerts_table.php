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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backlink_id')->constrained()->onDelete('cascade');
            $table->string('type', 50); // backlink_lost, backlink_changed, backlink_recovered
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable(); // Données additionnelles (old_status, new_status, etc.)
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes pour performance
            $table->index(['backlink_id', 'created_at']);
            $table->index(['is_read', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
