<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backlink_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('count_active')->default(0);
            $table->unsignedInteger('count_lost')->default(0);
            $table->unsignedInteger('count_changed')->default(0);
            $table->unsignedInteger('count_total')->default(0);
            $table->timestamps();

            // Un seul snapshot par jour par projet (null = global toutes projets)
            $table->unique(['snapshot_date', 'project_id']);
            $table->index('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backlink_snapshots');
    }
};
