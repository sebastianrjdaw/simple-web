<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->index(['media_type', 'status']);
            $table->index('file_size');
            $table->index('last_used_at');
            $table->index('created_at');
        });

        Schema::table('layouts', function (Blueprint $table) {
            $table->index(['state', 'version']);
            $table->index('template_key');
            $table->index('updated_at');
        });

        Schema::table('backups', function (Blueprint $table) {
            $table->index(['status', 'completed_at']);
            $table->index(['type', 'started_at']);
        });

        Schema::create('admin_activity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->string('subject_type', 80);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('result', 32);
            $table->unsignedBigInteger('bytes')->default(0);
            $table->json('details_json')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['action', 'result']);
            $table->index(['subject_type', 'subject_id']);
        });

        $now = now();
        foreach ([
            'backup_automatic_enabled' => '1',
            'backup_frequency_days' => '2',
            'backup_time' => '03:00',
            'backup_type' => 'configuration',
            'backup_retention_count' => '7',
            'backup_destination' => 'local',
        ] as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], [
                'value' => $value,
                'type' => 'string',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_events');

        Schema::table('backups', function (Blueprint $table) {
            $table->dropIndex(['status', 'completed_at']);
            $table->dropIndex(['type', 'started_at']);
        });

        Schema::table('layouts', function (Blueprint $table) {
            $table->dropIndex(['state', 'version']);
            $table->dropIndex(['template_key']);
            $table->dropIndex(['updated_at']);
        });

        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropIndex(['media_type', 'status']);
            $table->dropIndex(['file_size']);
            $table->dropIndex(['last_used_at']);
            $table->dropIndex(['created_at']);
        });
    }
};
