<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Indexes sur backlinks
        Schema::table('backlinks', function (Blueprint $table) {
            $table->index('status', 'idx_backlinks_status');
            $table->index(['project_id', 'status'], 'idx_backlinks_project_status');
            $table->index('last_checked_at', 'idx_backlinks_last_checked');
        });

        // Indexes sur alerts
        Schema::table('alerts', function (Blueprint $table) {
            $table->index(['backlink_id', 'is_read'], 'idx_alerts_backlink_read');
            $table->index('created_at', 'idx_alerts_created');
        });

        // Indexes sur domain_metrics
        Schema::table('domain_metrics', function (Blueprint $table) {
            $table->index('last_updated_at', 'idx_domain_metrics_updated');
            $table->index('domain', 'idx_domain_metrics_domain');
        });

        // Indexes sur orders
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['project_id', 'status'], 'idx_orders_project_status');
        });
    }

    public function down(): void
    {
        Schema::table('backlinks', function (Blueprint $table) {
            $table->dropIndex('idx_backlinks_status');
            $table->dropIndex('idx_backlinks_project_status');
            $table->dropIndex('idx_backlinks_last_checked');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex('idx_alerts_backlink_read');
            $table->dropIndex('idx_alerts_created');
        });

        Schema::table('domain_metrics', function (Blueprint $table) {
            $table->dropIndex('idx_domain_metrics_updated');
            $table->dropIndex('idx_domain_metrics_domain');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_project_status');
        });
    }
};
