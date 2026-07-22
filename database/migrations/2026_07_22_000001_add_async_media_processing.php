<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->foreignId('processing_result_media_id')->nullable()->after('validation_message')->constrained('media_assets')->nullOnDelete();
            $table->timestamp('processing_started_at')->nullable()->after('processing_result_media_id');
            $table->timestamp('processing_completed_at')->nullable()->after('processing_started_at');
            $table->index(['status', 'processing_completed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropIndex(['status', 'processing_completed_at']);
            $table->dropConstrainedForeignId('processing_result_media_id');
            $table->dropColumn(['processing_started_at', 'processing_completed_at']);
        });
    }
};
