<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_user_designations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('designation_id')->constrained('monitoring_designations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'designation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_user_designations');
    }
};
