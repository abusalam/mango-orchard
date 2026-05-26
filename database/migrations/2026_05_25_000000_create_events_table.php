<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->text('description');
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->nullable();
            $table->string('location', 180);
            $table->string('location_url', 500)->nullable();
            $table->string('host', 180)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('registration_url', 500)->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();

            $table->index(['status', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
