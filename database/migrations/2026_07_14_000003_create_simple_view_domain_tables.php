<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id(); $table->string('display_name'); $table->string('original_filename');
            $table->string('storage_path'); $table->string('thumbnail_path')->nullable();
            $table->string('mime_type'); $table->string('media_type', 16); $table->string('extension', 12);
            $table->unsignedBigInteger('file_size'); $table->string('sha256', 64)->unique();
            $table->unsignedInteger('width')->nullable(); $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable(); $table->string('video_codec')->nullable();
            $table->string('status', 16)->default('processing'); $table->text('validation_message')->nullable();
            $table->timestamps();
        });
        Schema::create('layouts', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('template_key')->default('full');
            $table->string('state', 16)->default('draft'); $table->unsignedBigInteger('version')->default(0);
            $table->json('snapshot_json')->nullable(); $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('layout_zones', function (Blueprint $table) {
            $table->id(); $table->foreignId('layout_id')->constrained()->cascadeOnDelete();
            $table->string('zone_key'); $table->unsignedTinyInteger('position');
            $table->string('image_fit_default', 16)->default('cover');
            $table->unsignedInteger('image_duration_default_ms')->default(10000);
            $table->string('transition_type', 16)->default('fade');
            $table->unsignedInteger('transition_duration_ms')->default(500); $table->timestamps();
            $table->unique(['layout_id', 'zone_key']);
        });
        Schema::create('playlist_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('layout_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0); $table->unsignedInteger('image_duration_ms')->nullable();
            $table->string('image_fit', 16)->nullable(); $table->timestamps();
        });
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id(); $table->unsignedTinyInteger('weekday')->unique(); $table->boolean('is_closed')->default(false);
            $table->time('first_start')->nullable(); $table->time('first_end')->nullable();
            $table->time('second_start')->nullable(); $table->time('second_end')->nullable(); $table->timestamps();
        });
        Schema::create('display_statuses', function (Blueprint $table) {
            $table->id(); $table->string('display_key')->unique(); $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('loaded_publication_version')->default(0); $table->unsignedInteger('screen_width')->nullable();
            $table->unsignedInteger('screen_height')->nullable(); $table->string('state', 32)->default('offline');
            $table->json('current_items_json')->nullable(); $table->text('last_error')->nullable(); $table->timestamps();
        });
        Schema::create('playback_errors', function (Blueprint $table) {
            $table->id(); $table->foreignId('media_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('zone_key')->nullable(); $table->unsignedBigInteger('publication_version')->nullable();
            $table->text('message'); $table->json('context_json')->nullable(); $table->timestamp('occurred_at');
            $table->timestamp('resolved_at')->nullable(); $table->timestamps();
        });
        Schema::create('backups', function (Blueprint $table) {
            $table->id(); $table->string('filename'); $table->string('path'); $table->string('type', 20);
            $table->unsignedBigInteger('size')->default(0); $table->string('status', 20)->default('processing');
            $table->timestamp('started_at'); $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable(); $table->timestamps();
        });
        $now = now();
        foreach ([
            'application_name'=>'Simple View','timezone'=>'Europe/Madrid','default_image_duration_ms'=>'10000',
            'default_image_fit'=>'cover','default_transition_type'=>'fade','default_transition_duration_ms'=>'500',
            'max_upload_size_bytes'=>(string)(2*1024*1024*1024),'storage_warning_percentage'=>'80',
            'storage_block_percentage'=>'90','after_hours_mode'=>'fallback','playback_override'=>'normal',
            'active_publication_version'=>'0','fallback_media_asset_id'=>'',
        ] as $key=>$value) \DB::table('settings')->updateOrInsert(['key'=>$key],['value'=>$value,'type'=>'string','created_at'=>$now,'updated_at'=>$now]);
        for($day=1;$day<=7;$day++) \DB::table('business_hours')->insert(['weekday'=>$day,'is_closed'=>false,'first_start'=>'00:00','first_end'=>'23:59','created_at'=>$now,'updated_at'=>$now]);
    }

    public function down(): void
    {
        Schema::dropIfExists('backups'); Schema::dropIfExists('playback_errors');
        Schema::dropIfExists('display_statuses'); Schema::dropIfExists('business_hours');
        Schema::dropIfExists('playlist_items'); Schema::dropIfExists('layout_zones');
        Schema::dropIfExists('layouts'); Schema::dropIfExists('media_assets');
    }
};
