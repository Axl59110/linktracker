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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('backlink_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 50)->default('pending');
            // pending, in_progress, published, cancelled, refunded

            $table->string('target_url');
            $table->string('source_url')->nullable();
            $table->string('anchor_text', 255)->nullable();
            $table->string('tier_level', 20)->default('tier1');
            $table->string('spot_type', 20)->default('external');

            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 10)->default('EUR');
            $table->boolean('invoice_paid')->default(false);

            $table->date('ordered_at')->nullable();
            $table->date('expected_at')->nullable();
            $table->date('published_at')->nullable();

            $table->string('contact_name', 255)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('project_id');
            $table->index('status');
            $table->index('platform_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
