<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('storage_snapshots', function (Blueprint $table) {
            $table->id();
            foreach (['filesystem_total_bytes', 'filesystem_used_bytes', 'filesystem_free_bytes', 'reserved_bytes',
                'media_bytes', 'thumbnails_bytes', 'database_bytes', 'backups_bytes', 'logs_bytes', 'cache_bytes',
                'temp_bytes', 'project_bytes'] as $column) $table->unsignedBigInteger($column)->default(0);
            $table->unsignedBigInteger('docker_bytes')->nullable();
            $table->unsignedBigInteger('docker_reclaimable_bytes')->nullable();
            $table->unsignedBigInteger('other_bytes')->nullable();
            $table->string('status', 16);
            $table->timestamp('source_measured_at');
            $table->json('details_json')->nullable();
            $table->timestamps();
            $table->index('source_measured_at');
        });

        Schema::table('media_assets', function (Blueprint $table) {
            $table->timestamp('last_used_at')->nullable()->after('validation_message');
            $table->timestamp('storage_verified_at')->nullable()->after('last_used_at');
            $table->softDeletes();
        });

        Schema::table('playlist_items', function (Blueprint $table) {
            $table->string('transition_type', 16)->nullable()->after('image_fit');
            $table->unsignedInteger('transition_duration_ms')->nullable()->after('transition_type');
        });
    }

    public function down(): void
    {
        Schema::table('playlist_items', fn (Blueprint $table) => $table->dropColumn(['transition_type', 'transition_duration_ms']));
        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['last_used_at', 'storage_verified_at']);
        });
        Schema::dropIfExists('storage_snapshots');
    }
};
