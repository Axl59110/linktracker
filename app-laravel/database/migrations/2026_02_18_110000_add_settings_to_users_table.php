<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Fréquence de vérification des backlinks
            $table->string('check_frequency', 20)->default('daily')->after('webhook_events');
            // Timeout HTTP pour les vérifications (en secondes)
            $table->unsignedTinyInteger('http_timeout')->default(30)->after('check_frequency');
            // Notifications email
            $table->boolean('email_alerts_enabled')->default(true)->after('http_timeout');
            // Provider SEO actif pour cet utilisateur
            $table->string('seo_provider', 20)->default('custom')->after('email_alerts_enabled');
            // API key SEO chiffrée
            $table->text('seo_api_key_encrypted')->nullable()->after('seo_provider');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'check_frequency',
                'http_timeout',
                'email_alerts_enabled',
                'seo_provider',
                'seo_api_key_encrypted',
            ]);
        });
    }
};
