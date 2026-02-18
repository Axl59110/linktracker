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
        Schema::create('domain_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique(); // ex: example.com
            $table->unsignedTinyInteger('da')->nullable();           // Domain Authority (Moz) 0-100
            $table->unsignedTinyInteger('dr')->nullable();           // Domain Rating (Ahrefs) 0-100
            $table->unsignedTinyInteger('tf')->nullable();           // Trust Flow (Majestic) 0-100
            $table->unsignedTinyInteger('cf')->nullable();           // Citation Flow (Majestic) 0-100
            $table->unsignedTinyInteger('spam_score')->nullable();   // Spam Score (Moz) 0-100
            $table->unsignedBigInteger('backlinks_count')->nullable(); // Nombre de backlinks
            $table->string('provider', 50)->default('custom');       // moz, ahrefs, majestic, custom
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->index('last_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_metrics');
    }
};
