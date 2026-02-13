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
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Getlinko", "Semrush Marketplace", "Direct Contact"
            $table->string('url')->nullable(); // Ex: "https://getlinko.com"
            $table->text('description')->nullable();
            $table->enum('type', ['marketplace', 'direct', 'other'])->default('marketplace');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};
