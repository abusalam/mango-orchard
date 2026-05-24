<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mango_varieties', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('origin');
            $table->string('season');
            $table->unsignedTinyInteger('season_start');
            $table->unsignedTinyInteger('season_end');
            $table->text('flavor');
            $table->json('tags');
            $table->string('theme')->default('sunrise');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mango_varieties');
    }
};
