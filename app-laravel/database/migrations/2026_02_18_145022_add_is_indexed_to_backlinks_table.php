<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backlinks', function (Blueprint $table) {
            $table->boolean('is_indexed')->nullable()->after('is_dofollow');
        });
    }

    public function down(): void
    {
        Schema::table('backlinks', function (Blueprint $table) {
            $table->dropColumn('is_indexed');
        });
    }
};
