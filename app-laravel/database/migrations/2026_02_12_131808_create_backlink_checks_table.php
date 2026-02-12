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
        Schema::create('backlink_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backlink_id')->constrained()->onDelete('cascade');
            $table->integer('http_status')->nullable();
            $table->boolean('is_present')->default(false);
            $table->text('anchor_text')->nullable();
            $table->string('rel_attributes', 100)->nullable();
            $table->integer('response_time')->nullable(); // milliseconds
            $table->timestamp('checked_at')->useCurrent();
            $table->timestamps();

            // Index for efficient queries
            $table->index(['backlink_id', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlink_checks');
    }
};
