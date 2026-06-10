<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_album_id')->constrained('gallery_albums')->cascadeOnDelete();
            $table->string('path');                   // e.g. "gallery/himsagar/abc.webp"
            $table->string('thumbnail_path')->nullable();
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();

            $table->index(['gallery_album_id', 'position']);
        });

        // Now we can add the FK from albums.cover_photo_id → photos.id
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->foreign('cover_photo_id')->references('id')->on('gallery_photos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->dropForeign(['cover_photo_id']);
        });
        Schema::dropIfExists('gallery_photos');
    }
};
