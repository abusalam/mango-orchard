<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mango_variety_id')->constrained()->cascadeOnDelete();
            $table->string('farm_name');
            $table->string('location');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('availability_start_month');
            $table->unsignedTinyInteger('availability_end_month');
            $table->decimal('price_per_kg', 8, 2)->nullable();
            $table->unsignedInteger('quantity_available_kg')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();

            $table->index(['status', 'mango_variety_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
