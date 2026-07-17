<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->string('provider', 40)->nullable()->after('media_type');
            $table->text('embed_url')->nullable()->after('provider');
            $table->json('embed_options_json')->nullable()->after('embed_url');
            $table->foreignId('fallback_media_asset_id')->nullable()->after('embed_options_json')->constrained('media_assets')->nullOnDelete();
            $table->string('validation_status', 32)->nullable()->after('fallback_media_asset_id');
            $table->index(['media_type', 'provider']);
            $table->index('validation_status');
        });
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fallback_media_asset_id');
            $table->dropIndex(['media_type', 'provider']);
            $table->dropIndex(['validation_status']);
            $table->dropColumn(['provider', 'embed_url', 'embed_options_json', 'validation_status']);
        });
    }
};
