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
        Schema::table('backlinks', function (Blueprint $table) {
            // Add indexes for foreign keys and frequently queried fields
            $table->index('parent_backlink_id');
            $table->index('platform_id');
            $table->index('created_by_user_id');
            $table->index('tier_level');
            $table->index('spot_type');
            $table->index('published_at');
        });

        Schema::table('platforms', function (Blueprint $table) {
            // Add indexes for frequently queried fields
            $table->index('name');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backlinks', function (Blueprint $table) {
            $table->dropIndex(['parent_backlink_id']);
            $table->dropIndex(['platform_id']);
            $table->dropIndex(['created_by_user_id']);
            $table->dropIndex(['tier_level']);
            $table->dropIndex(['spot_type']);
            $table->dropIndex(['published_at']);
        });

        Schema::table('platforms', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['type']);
            $table->dropIndex(['is_active']);
        });
    }
};
