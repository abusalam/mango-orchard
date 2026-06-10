<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            // Cover photo FK is added in the photos migration so the
            // forward reference can be created cleanly. Nullable here.
            $table->unsignedBigInteger('cover_photo_id')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('published')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['published', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_albums');
    }
};
