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
        Schema::create('backlinks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->text('source_url');
            $table->text('target_url');
            $table->text('anchor_text')->nullable();
            $table->string('status', 50)->default('active'); // active, lost, changed
            $table->integer('http_status')->nullable();
            $table->string('rel_attributes', 100)->nullable(); // follow, nofollow
            $table->boolean('is_dofollow')->default(true);
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            // Indexes pour performance
            $table->index(['project_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlinks');
    }
};
